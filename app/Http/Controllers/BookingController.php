<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    protected $googleSheets;

    public function __construct(GoogleSheetsService $googleSheets)
    {
        $this->googleSheets = $googleSheets;
    }

    public function index()
    {
        $reservations = Reservation::all();
        return response()->json(['reservations' => $reservations]);
    }

    public function store(Request $request)
    {
        $is_temporary = filter_var($request->input('is_temporary', false), FILTER_VALIDATE_BOOLEAN);

        $rules = [
            'category' => 'required|in:platinum,gold,standing',
            'name' => 'required|string|max:255',
            'ticket_count' => 'nullable|integer|min:1',
        ];

        if (!$is_temporary) {
            $rules['email'] = 'required|email|max:255';
            $rules['ticket_number'] = 'required|string|max:255';
        } else {
            $rules['email'] = 'nullable|email|max:255';
            $rules['ticket_number'] = 'nullable|string|max:255';
        }

        $request->validate($rules);

        $category = $request->category;

        // Standing Area booking logic
        if ($category === 'standing') {
            $ticket_count = (int)$request->input('ticket_count', 1);

            $baseTicket = $request->ticket_number;
            if (empty($baseTicket)) {
                $baseTicket = 'TEMP-STD-' . strtoupper(substr($request->side ?? 'center', 0, 1)) . '-' . time();
            }

            $reservations = [];
            for ($i = 1; $i <= $ticket_count; $i++) {
                $reservations[] = Reservation::create([
                    'category' => 'standing',
                    'side' => $request->side ?? 'center',
                    'name' => $request->name,
                    'email' => $request->email,
                    'ticket_number' => $baseTicket,
                    'is_full_table' => false,
                    'is_temporary' => $is_temporary,
                ]);
            }

            // Sync to Google Sheet ONLY for permanent bookings
            if (!$is_temporary) {
                if (function_exists('Illuminate\Support\defer')) {
                    \Illuminate\Support\defer(function () use ($reservations) {
                        $this->googleSheets->appendReservations($reservations);
                    });
                } else {
                    $this->googleSheets->appendReservations($reservations);
                }
            }

            $typeLabel = $is_temporary ? 'Temporary standing reservation' : 'Standing booking';
            return response()->json([
                'success' => true,
                'message' => $ticket_count === 1 
                    ? "{$typeLabel} created successfully." 
                    : "{$ticket_count} {$typeLabel}s created successfully.",
                'reservation' => $reservations[0],
                'reservations' => $reservations,
                'is_temporary' => $is_temporary,
            ]);
        }

        // Table booking logic (Platinum/Gold)
        $request->validate([
            'side' => 'required|in:left,right',
            'table_number' => 'required|integer',
        ]);

        $side = $request->side;
        $table_number = (int)$request->table_number;
        $is_full_table = filter_var($request->is_full_table, FILTER_VALIDATE_BOOLEAN);

        // Confirm bounds
        if ($category === 'platinum' && ($table_number < 1 || $table_number > 54)) {
            return response()->json(['success' => false, 'message' => 'Invalid Platinum table number.'], 422);
        }
        if ($category === 'gold' && ($table_number < 55 || $table_number > 126)) {
            return response()->json(['success' => false, 'message' => 'Invalid Gold table number.'], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $category, $side, $table_number, $is_full_table, $is_temporary) {
                // Check if table is already fully reserved or held
                $isTableFullyReserved = Reservation::where('category', $category)
                    ->where('side', $side)
                    ->where('table_number', $table_number)
                    ->where('is_full_table', true)
                    ->lockForUpdate()
                    ->first();

                if ($isTableFullyReserved) {
                    $statusDesc = $isTableFullyReserved->is_temporary ? 'temporarily held by ' . $isTableFullyReserved->name : 'already fully reserved';
                    throw new \Exception("This table is {$statusDesc}.");
                }

                if ($is_full_table) {
                    // Check if any individual seats have been booked on this table
                    $hasIndividualBookings = Reservation::where('category', $category)
                        ->where('side', $side)
                        ->where('table_number', $table_number)
                        ->lockForUpdate()
                        ->first();

                    if ($hasIndividualBookings) {
                        throw new \Exception('Table has active individual seat bookings. Cannot reserve full table.');
                    }

                    $ticketNumber = $request->ticket_number;
                    if (empty($ticketNumber)) {
                        $ticketNumber = 'TEMP-' . strtoupper(substr($side, 0, 1)) . $table_number . '-' . strtoupper(substr(uniqid(), -4));
                    }

                    $reservation = Reservation::create([
                        'category' => $category,
                        'side' => $side,
                        'table_number' => $table_number,
                        'is_full_table' => true,
                        'is_temporary' => $is_temporary,
                        'name' => $request->name,
                        'email' => $request->email,
                        'ticket_number' => $ticketNumber,
                    ]);

                    return [$reservation];
                } else {
                    // Individual seat booking (one or more seats)
                    $seat_numbers = $this->resolveSeatNumbers($request);

                    // Check if any of the requested seats are already booked
                    $alreadyBooked = Reservation::where('category', $category)
                        ->where('side', $side)
                        ->where('table_number', $table_number)
                        ->whereIn('seat_number', $seat_numbers)
                        ->lockForUpdate()
                        ->pluck('seat_number')
                        ->all();

                    if (count($alreadyBooked) > 0) {
                        sort($alreadyBooked);
                        $label = count($alreadyBooked) === 1 ? 'Seat' : 'Seats';
                        throw new \Exception("{$label} " . implode(', ', $alreadyBooked) . ' already reserved.');
                    }

                    $reservations = [];
                    foreach ($seat_numbers as $seat_number) {
                        $ticketNumber = $request->ticket_number;
                        if (empty($ticketNumber)) {
                            $ticketNumber = 'TEMP-' . strtoupper(substr($side, 0, 1)) . $table_number . '-S' . $seat_number . '-' . strtoupper(substr(uniqid(), -4));
                        }

                        $reservations[] = Reservation::create([
                            'category' => $category,
                            'side' => $side,
                            'table_number' => $table_number,
                            'seat_number' => $seat_number,
                            'is_full_table' => false,
                            'is_temporary' => $is_temporary,
                            'name' => $request->name,
                            'email' => $request->email,
                            'ticket_number' => $ticketNumber,
                        ]);
                    }

                    return $reservations;
                }
            });

            // Sync created reservations to Google Sheet ONLY for permanent bookings!
            if (!$is_temporary) {
                if (function_exists('Illuminate\Support\defer')) {
                    \Illuminate\Support\defer(function () use ($result) {
                        $this->googleSheets->appendReservations($result);
                    });
                } else {
                    $this->googleSheets->appendReservations($result);
                }
            }

            $seatCount = count($result);
            if ($is_temporary) {
                if ($is_full_table) {
                    $message = "Full table temporarily reserved for {$request->name} (Not synced to Excel).";
                } else {
                    $message = $seatCount === 1
                        ? "Seat temporarily reserved for {$request->name} (Not synced to Excel)."
                        : "{$seatCount} seats temporarily reserved for {$request->name} (Not synced to Excel).";
                }
            } else {
                if ($is_full_table) {
                    $message = 'Full table reserved successfully and synced to Excel.';
                } else {
                    $message = $seatCount === 1
                        ? 'Seat reserved successfully and synced to Excel.'
                        : "{$seatCount} seats reserved successfully and synced to Excel.";
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'reservation' => $result[0],
                'reservations' => $result,
                'is_temporary' => $is_temporary,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Resolve and validate the requested seat numbers for an individual booking.
     *
     * @return int[] Unique, validated seat numbers (1-8).
     */
    private function resolveSeatNumbers(Request $request): array
    {
        $raw = $request->input('seat_numbers', $request->input('seat_number'));

        if (!is_array($raw)) {
            $raw = $raw === null || $raw === '' ? [] : [$raw];
        }

        $seat_numbers = array_values(array_unique(array_map('intval', $raw)));

        if (count($seat_numbers) === 0) {
            throw new \Exception('At least one seat (1-8) is required for individual booking.');
        }

        foreach ($seat_numbers as $seat_number) {
            if ($seat_number < 1 || $seat_number > 8) {
                throw new \Exception('Seat number must be between 1 and 8.');
            }
        }

        sort($seat_numbers);

        return $seat_numbers;
    }

    public function destroy($id)
    {
        try {
            $reservation = Reservation::find($id);
            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found.'], 404);
            }

            $wasTemporary = (bool)$reservation->is_temporary;

            // Sync deletion to Google Sheet ONLY if it was a permanent booking
            if (!$wasTemporary) {
                if (function_exists('Illuminate\Support\defer')) {
                    \Illuminate\Support\defer(function () use ($reservation) {
                        $this->googleSheets->deleteReservation($reservation);
                    });
                } else {
                    $this->googleSheets->deleteReservation($reservation);
                }
            }

            // Delete from Database
            $reservation->delete();

            return response()->json([
                'success' => true,
                'message' => $wasTemporary 
                    ? 'Temporary reservation released successfully.' 
                    : 'Reservation cancelled/deleted successfully from database and Excel.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $reservation = Reservation::find($id);
            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found.'], 404);
            }

            $wasTemporary = (bool)$reservation->is_temporary;
            $makePermanent = filter_var($request->input('make_permanent', false), FILTER_VALIDATE_BOOLEAN)
                || ($request->has('is_temporary') && !filter_var($request->input('is_temporary'), FILTER_VALIDATE_BOOLEAN));

            if ($wasTemporary && $makePermanent) {
                // Converting temporary reservation to permanent
                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|max:255',
                    'ticket_number' => 'required|string|max:255',
                ]);

                $reservation->name = $request->input('name');
                $reservation->email = $request->input('email');
                $reservation->ticket_number = $request->input('ticket_number');
                $reservation->is_temporary = false;
                $reservation->save();

                // Now that it's permanent, sync to Google Sheet!
                if (function_exists('Illuminate\Support\defer')) {
                    \Illuminate\Support\defer(function () use ($reservation) {
                        $this->googleSheets->appendReservations([$reservation]);
                    });
                } else {
                    $this->googleSheets->appendReservations([$reservation]);
                }

                return response()->json([
                    'success' => true,
                    'message' => "Reservation for {$reservation->name} is now PERMANENT and synced to Excel sheet!",
                    'reservation' => $reservation
                ]);
            }

            // Normal update (or editing details of temporary hold)
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => $wasTemporary ? 'nullable|email|max:255' : 'sometimes|required|email|max:255',
                'ticket_number' => $wasTemporary ? 'nullable|string|max:255' : 'sometimes|required|string|max:255',
            ]);

            $oldTicketNumber = $reservation->ticket_number;

            if ($request->has('name')) {
                $reservation->name = $request->input('name');
            }
            if ($request->has('email')) {
                $reservation->email = $request->input('email');
            }
            if ($request->has('ticket_number')) {
                $reservation->ticket_number = $request->input('ticket_number');
            }
            if ($request->has('is_temporary')) {
                $reservation->is_temporary = filter_var($request->input('is_temporary'), FILTER_VALIDATE_BOOLEAN);
            }

            $reservation->save();

            // Only sync update to Google Sheet if reservation is permanent!
            if (!$reservation->is_temporary) {
                if (function_exists('Illuminate\Support\defer')) {
                    \Illuminate\Support\defer(function () use ($reservation, $oldTicketNumber) {
                        $this->googleSheets->updateReservation($reservation, $oldTicketNumber);
                    });
                } else {
                    $this->googleSheets->updateReservation($reservation, $oldTicketNumber);
                }
            }

            return response()->json([
                'success' => true,
                'message' => $reservation->is_temporary 
                    ? 'Temporary reservation updated successfully.' 
                    : 'Reservation updated successfully and synced to Excel.',
                'reservation' => $reservation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

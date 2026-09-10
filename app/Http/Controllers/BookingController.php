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
        $request->validate([
            'category' => 'required|in:platinum,gold,standing',
            'name' => 'required|string',
            'email' => 'required|email',
            'ticket_number' => 'required|string',
            'ticket_count' => 'nullable|integer|min:1',
        ]);

        $category = $request->category;

        // Standing Area booking logic
        if ($category === 'standing') {
            $ticket_count = (int)$request->input('ticket_count', 1);

            $reservations = [];
            for ($i = 1; $i <= $ticket_count; $i++) {
                $tkt = $request->ticket_number;
                if ($ticket_count > 1) {
                    $tkt = $tkt . '-' . $i;
                }

                $reservations[] = Reservation::create([
                    'category' => 'standing',
                    'side' => $request->side ?? 'center',
                    'name' => $request->name,
                    'email' => $request->email,
                    'ticket_number' => $tkt,
                    'is_full_table' => false,
                ]);
            }

            // Sync to Google Sheet in background batch
            if (function_exists('Illuminate\Support\defer')) {
                \Illuminate\Support\defer(function () use ($reservations) {
                    $this->googleSheets->appendReservations($reservations);
                });
            } else {
                $this->googleSheets->appendReservations($reservations);
            }

            return response()->json([
                'success' => true,
                'message' => $ticket_count === 1 
                    ? 'Standing booking created successfully.' 
                    : "{$ticket_count} standing bookings created successfully.",
                'reservation' => $reservations[0],
                'reservations' => $reservations
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
            $result = DB::transaction(function () use ($request, $category, $side, $table_number, $is_full_table) {
                // Check if table is already fully reserved
                $isTableFullyReserved = Reservation::where('category', $category)
                    ->where('side', $side)
                    ->where('table_number', $table_number)
                    ->where('is_full_table', true)
                    ->lockForUpdate()
                    ->first();

                if ($isTableFullyReserved) {
                    throw new \Exception('This table is already fully reserved.');
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

                    $reservation = Reservation::create([
                        'category' => $category,
                        'side' => $side,
                        'table_number' => $table_number,
                        'is_full_table' => true,
                        'name' => $request->name,
                        'email' => $request->email,
                        'ticket_number' => $request->ticket_number,
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
                        $reservations[] = Reservation::create([
                            'category' => $category,
                            'side' => $side,
                            'table_number' => $table_number,
                            'seat_number' => $seat_number,
                            'is_full_table' => false,
                            'name' => $request->name,
                            'email' => $request->email,
                            'ticket_number' => $request->ticket_number,
                        ]);
                    }

                    return $reservations;
                }
            });

            // Sync created reservations to Google Sheet in background batch
            if (function_exists('Illuminate\Support\defer')) {
                \Illuminate\Support\defer(function () use ($result) {
                    $this->googleSheets->appendReservations($result);
                });
            } else {
                $this->googleSheets->appendReservations($result);
            }

            $seatCount = count($result);
            if ($is_full_table) {
                $message = 'Full table reserved successfully.';
            } else {
                $message = $seatCount === 1
                    ? 'Seat reserved successfully.'
                    : "{$seatCount} seats reserved successfully.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'reservation' => $result[0],
                'reservations' => $result,
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
     * Accepts either `seat_numbers` (array) or the legacy single `seat_number`.
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

            // Sync deletion to Google Sheet in background
            if (function_exists('Illuminate\Support\defer')) {
                \Illuminate\Support\defer(function () use ($reservation) {
                    $this->googleSheets->deleteReservation($reservation);
                });
            } else {
                $this->googleSheets->deleteReservation($reservation);
            }

            // Delete from Database
            $reservation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reservation cancelled/deleted successfully.'
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

            $request->validate([
                'name' => 'sometimes|required|string',
                'email' => 'sometimes|required|email',
                'ticket_number' => 'sometimes|required|string',
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

            $reservation->save();

            // Sync update to Google Sheet in background
            if (function_exists('Illuminate\Support\defer')) {
                \Illuminate\Support\defer(function () use ($reservation, $oldTicketNumber) {
                    $this->googleSheets->updateReservation($reservation, $oldTicketNumber);
                });
            } else {
                $this->googleSheets->updateReservation($reservation, $oldTicketNumber);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reservation updated successfully.',
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

<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArenaV2Controller extends Controller
{
    protected $googleSheets;

    public function __construct(GoogleSheetsService $googleSheets)
    {
        $this->googleSheets = $googleSheets;
    }

    /**
     * Render the V2 Admin Dashboard (13x10 Tables Map - 130 Tables, 8 Seats per Table, Numbered 1 to 130).
     * Rows 1-6 (Tables 1-60): Platinum (60 tables * 8 seats = 480 seats)
     * Rows 7-13 (Tables 61-130): Gold (70 tables * 8 seats = 560 seats)
     * Bottom Left & Right: Standing Areas + Center FOH & Entrance
     */
    public function index()
    {
        $stats = $this->calculateStats();
        $recentBookings = Reservation::mapV2()->orderBy('id', 'desc')->take(15)->get();

        return view('v2-dashboard', [
            'stats' => $stats,
            'recentBookings' => $recentBookings,
        ]);
    }

    /**
     * Get all V2 bookings for the 13x10 table map.
     */
    public function bookingsIndex()
    {
        $reservations = Reservation::mapV2()->get();
        return response()->json(['reservations' => $reservations]);
    }

    /**
     * Store a V2 table (full table or seats 1-8) or standing booking.
     */
    public function bookingsStore(Request $request)
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

        $category = strtolower($request->category);

        // 1. Standing Area Booking (Left or Right Standing Area)
        if ($category === 'standing') {
            $side = strtolower((string)$request->input('side', 'left'));
            if (!in_array($side, ['left', 'right', 'center'], true)) {
                $side = 'left';
            }
            $ticket_count = max(1, (int)$request->input('ticket_count', 1));

            $baseTicket = trim((string)$request->input('ticket_number', ''));
            if (empty($baseTicket)) {
                $baseTicket = 'TEMP-V2-STD-' . strtoupper(substr($side, 0, 1)) . '-' . time();
            }

            $reservations = [];
            for ($i = 1; $i <= $ticket_count; $i++) {
                $reservations[] = Reservation::create([
                    'map_type' => 'v2',
                    'category' => 'standing',
                    'side' => $side,
                    'name' => $request->name,
                    'email' => $request->email,
                    'ticket_number' => $baseTicket,
                    'is_full_table' => false,
                    'is_temporary' => $is_temporary,
                ]);
            }

            if (!$is_temporary) {
                $this->syncAppendToSheet($reservations);
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

        // 2. Table Booking Logic (Tables 1 to 130, 8 Seats per Table)
        $request->validate([
            'table_number' => 'required|integer|min:1|max:130',
        ]);

        $table_number = (int)$request->table_number;
        $is_full_table = filter_var($request->is_full_table, FILTER_VALIDATE_BOOLEAN);

        // Tables 1-60 = Platinum (Rows 1-6), Tables 61-130 = Gold (Rows 7-13)
        $category = $table_number <= 60 ? 'platinum' : 'gold';
        $side = 'center';

        try {
            $result = DB::transaction(function () use ($request, $category, $side, $table_number, $is_full_table, $is_temporary) {
                // Check if table is already fully reserved or held
                $isTableFullyReserved = Reservation::mapV2()
                    ->where('table_number', $table_number)
                    ->where('is_full_table', true)
                    ->lockForUpdate()
                    ->first();

                if ($isTableFullyReserved) {
                    $statusDesc = $isTableFullyReserved->is_temporary
                        ? 'temporarily held by ' . $isTableFullyReserved->name
                        : 'already fully reserved';
                    throw new \Exception("Table {$table_number} is {$statusDesc}.");
                }

                if ($is_full_table) {
                    $hasIndividualBookings = Reservation::mapV2()
                        ->where('table_number', $table_number)
                        ->lockForUpdate()
                        ->first();

                    if ($hasIndividualBookings) {
                        throw new \Exception("Table {$table_number} has active individual seat bookings. Cannot reserve full table.");
                    }

                    $ticketNumber = trim((string)$request->ticket_number);
                    if (empty($ticketNumber)) {
                        $ticketNumber = 'TEMP-V2-T' . $table_number . '-' . strtoupper(substr(uniqid(), -4));
                    }

                    $reservation = Reservation::create([
                        'map_type' => 'v2',
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
                    $seat_numbers = $this->resolveSeatNumbers($request);

                    $alreadyBooked = Reservation::mapV2()
                        ->where('table_number', $table_number)
                        ->whereIn('seat_number', $seat_numbers)
                        ->lockForUpdate()
                        ->pluck('seat_number')
                        ->all();

                    if (count($alreadyBooked) > 0) {
                        sort($alreadyBooked);
                        $label = count($alreadyBooked) === 1 ? 'Seat' : 'Seats';
                        throw new \Exception("{$label} " . implode(', ', $alreadyBooked) . " on Table {$table_number} already reserved.");
                    }

                    $sharedTicket = trim((string)$request->ticket_number);
                    if (empty($sharedTicket)) {
                        $sharedTicket = 'TEMP-V2-T' . $table_number . '-S' . implode('', $seat_numbers) . '-' . strtoupper(substr(uniqid(), -4));
                    }

                    $reservations = [];
                    foreach ($seat_numbers as $seat_number) {
                        $reservations[] = Reservation::create([
                            'map_type' => 'v2',
                            'category' => $category,
                            'side' => $side,
                            'table_number' => $table_number,
                            'seat_number' => $seat_number,
                            'is_full_table' => false,
                            'is_temporary' => $is_temporary,
                            'name' => $request->name,
                            'email' => $request->email,
                            'ticket_number' => $sharedTicket,
                        ]);
                    }

                    return $reservations;
                }
            });

            if (!$is_temporary) {
                $this->syncAppendToSheet($result);
            }

            $seatCount = count($result);
            if ($is_temporary) {
                $message = $is_full_table
                    ? "Full Table {$table_number} temporarily reserved for {$request->name} (Not synced to Excel)."
                    : ($seatCount === 1
                        ? "Seat temporarily reserved for {$request->name} (Not synced to Excel)."
                        : "{$seatCount} seats temporarily reserved for {$request->name} (Not synced to Excel).");
            } else {
                $message = $is_full_table
                    ? "Full Table {$table_number} reserved successfully and synced to Excel."
                    : ($seatCount === 1
                        ? 'Seat reserved successfully and synced to Excel.'
                        : "{$seatCount} seats reserved successfully and synced to Excel.");
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
                'message' => $e->getMessage(),
            ], 422);
        }
    }

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

    /**
     * Update a V2 reservation (or convert Temporary Hold -> Permanent and sync to Excel).
     */
    public function bookingsUpdate(Request $request, $id)
    {
        try {
            $reservation = Reservation::mapV2()->find($id);
            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found.'], 404);
            }

            $wasTemporary = (bool)$reservation->is_temporary;
            $makePermanent = filter_var($request->input('make_permanent', false), FILTER_VALIDATE_BOOLEAN)
                || ($request->has('is_temporary') && !filter_var($request->input('is_temporary'), FILTER_VALIDATE_BOOLEAN));

            if ($wasTemporary && $makePermanent) {
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

                $this->syncAppendToSheet([$reservation]);

                return response()->json([
                    'success' => true,
                    'message' => "Reservation for {$reservation->name} is now PERMANENT and synced to Excel sheet!",
                    'reservation' => $reservation,
                ]);
            }

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
                'message' => 'Reservation updated successfully.',
                'reservation' => $reservation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete / release a V2 reservation.
     */
    public function bookingsDestroy($id)
    {
        try {
            $reservation = Reservation::mapV2()->find($id);
            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found.'], 404);
            }

            $wasTemporary = (bool)$reservation->is_temporary;

            if (!$wasTemporary) {
                if (function_exists('Illuminate\Support\defer')) {
                    \Illuminate\Support\defer(function () use ($reservation) {
                        $this->googleSheets->deleteReservation($reservation);
                    });
                } else {
                    $this->googleSheets->deleteReservation($reservation);
                }
            }

            $reservation->delete();

            return response()->json([
                'success' => true,
                'message' => $wasTemporary
                    ? 'Temporary reservation released successfully.'
                    : 'Reservation cancelled/deleted successfully from database and Excel.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the V2 Admin QR Scanner & Check-In page.
     */
    public function scanner(Request $request)
    {
        $stats = $this->calculateCheckInStats();
        $initialQuery = trim($request->query('code', $request->query('order', '')));

        return view('v2-scanner', [
            'stats' => $stats,
            'initialQuery' => $initialQuery,
        ]);
    }

    /**
     * Lookup QR Code / Order No in V2 Seat Map.
     */
    public function checkInLookup(Request $request)
    {
        $rawInput = trim($request->input('qr_data', $request->input('code', '')));

        if (empty($rawInput)) {
            return response()->json([
                'success' => false,
                'found' => false,
                'message' => 'Please scan a QR code or enter an Order Number.',
            ], 422);
        }

        $parsed = $this->extractCodesFromQr($rawInput);
        $candidates = array_values(array_unique(array_filter([
            $parsed['order_no'],
            $parsed['ticket_code'],
            $parsed['raw_clean'],
        ])));

        $reservations = collect();

        foreach ($candidates as $candidate) {
            $found = Reservation::mapV2()
                ->whereRaw('LOWER(TRIM(ticket_number)) = ?', [strtolower(trim($candidate))])
                ->orderBy('seat_number', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($found->isNotEmpty()) {
                $reservations = $found;
                break;
            }
        }

        if ($reservations->isEmpty()) {
            foreach ($candidates as $candidate) {
                if (strlen($candidate) >= 5) {
                    $found = Reservation::mapV2()
                        ->whereRaw('LOWER(ticket_number) LIKE ?', ['%' . strtolower(trim($candidate)) . '%'])
                        ->orderBy('seat_number', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    if ($found->isNotEmpty()) {
                        $reservations = $found;
                        break;
                    }
                }
            }
        }

        if ($reservations->isEmpty()) {
            return response()->json([
                'success' => true,
                'found' => false,
                'extracted_order_no' => $parsed['order_no'] ?: $parsed['raw_clean'],
                'extracted_ticket_code' => $parsed['ticket_code'],
                'message' => 'No reservation found for Order No: ' . ($parsed['order_no'] ?: ($parsed['ticket_code'] ?: $rawInput)),
                'stats' => $this->calculateCheckInStats(),
            ]);
        }

        return response()->json([
            'success' => true,
            'found' => true,
            'extracted_order_no' => $parsed['order_no'] ?: $reservations->first()->ticket_number,
            'extracted_ticket_code' => $parsed['ticket_code'],
            'matched_ticket_number' => $reservations->first()->ticket_number,
            'booking' => $this->formatBookingGroup($reservations),
            'stats' => $this->calculateCheckInStats(),
        ]);
    }

    /**
     * Mark V2 reservation(s) as Checked-In (or undo check-in).
     */
    public function checkInMark(Request $request)
    {
        $status = $request->has('status')
            ? filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN)
            : true;

        $reservationIds = $request->input('reservation_ids', []);
        if (!is_array($reservationIds)) {
            $reservationIds = [$reservationIds];
        }
        if ($request->filled('reservation_id')) {
            $reservationIds[] = $request->input('reservation_id');
        }
        $reservationIds = array_values(array_unique(array_filter(array_map('intval', $reservationIds))));

        $ticketNumber = trim((string)$request->input('ticket_number', ''));

        $query = Reservation::mapV2();
        if (!empty($reservationIds)) {
            $query->whereIn('id', $reservationIds);
        } elseif (!empty($ticketNumber)) {
            $query->whereRaw('LOWER(TRIM(ticket_number)) = ?', [strtolower($ticketNumber)]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No reservation specified for check-in.',
            ], 422);
        }

        $targets = $query->get();
        if ($targets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation not found.',
            ], 404);
        }

        $now = $status ? now() : null;
        foreach ($targets as $res) {
            $res->is_checked_in = $status;
            $res->checked_in_at = $now;
            $res->save();
        }

        $primaryTicket = $targets->first()->ticket_number;
        $groupReservations = Reservation::mapV2()
            ->whereRaw('LOWER(TRIM(ticket_number)) = ?', [strtolower(trim($primaryTicket))])
            ->orderBy('seat_number', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $first = $groupReservations->first();
        $actionText = $status ? 'Checked-In' : 'Check-In Reset';

        return response()->json([
            'success' => true,
            'message' => "{$first->name} ({$first->ticket_number}) marked as {$actionText}!",
            'booking' => $this->formatBookingGroup($groupReservations),
            'stats' => $this->calculateCheckInStats(),
        ]);
    }

    /**
     * Public Anonymous Customer Seat Locator for V2 13x10 Table Map (Tables 1-130, 8 Seats per Table).
     */
    public function customerSeat(Request $request, $ticket = null)
    {
        $ticketQuery = trim($ticket ?? $request->query('ticket', $request->query('code', '')));

        $customerBookings = collect();
        $customerInfo = null;

        if (!empty($ticketQuery)) {
            $customerBookings = Reservation::mapV2()
                ->whereRaw('LOWER(TRIM(ticket_number)) = ?', [strtolower($ticketQuery)])
                ->where(function ($q) {
                    $q->where('is_temporary', false)->orWhereNull('is_temporary');
                })
                ->orderBy('seat_number', 'asc')
                ->get();

            if ($customerBookings->isNotEmpty()) {
                $first = $customerBookings->first();
                $seats = $customerBookings->pluck('seat_number')->filter()->map(fn($s) => (int)$s)->values()->all();
                sort($seats);

                $checkedInSeats = $customerBookings->where('is_checked_in', true)
                    ->pluck('seat_number')->filter()->map(fn($s) => (int)$s)->values()->all();
                if ($first->is_full_table && $first->is_checked_in) {
                    $checkedInSeats = [1, 2, 3, 4, 5, 6, 7, 8];
                }

                $customerInfo = [
                    'name' => $first->name,
                    'ticket_number' => $first->ticket_number,
                    'category' => strtolower($first->category),
                    'side' => strtolower($first->side ?? 'center'),
                    'table_number' => $first->table_number ? (int)$first->table_number : null,
                    'row_number' => $first->table_number ? (int)ceil($first->table_number / 10) : null,
                    'col_number' => $first->table_number ? ((($first->table_number - 1) % 10) + 1) : null,
                    'is_full_table' => (bool)$first->is_full_table,
                    'seats' => $seats,
                    'checked_in_seats' => $checkedInSeats,
                    'seat_count' => $first->is_full_table ? 8 : max(count($seats), $customerBookings->count()),
                ];
            }
        }

        return view('v2-customer-seat', [
            'customerInfo' => $customerInfo,
            'ticketQuery' => $ticketQuery,
        ]);
    }

    private function formatBookingGroup($reservations): array
    {
        $first = $reservations->first();
        $isFullTable = (bool)$first->is_full_table;
        $category = strtolower($first->category);
        $isSeated = in_array($category, ['platinum', 'gold'], true);

        $seats = [];
        $checkedInSeats = [];

        if ($isFullTable) {
            $seats = [1, 2, 3, 4, 5, 6, 7, 8];
            if ($first->is_checked_in) {
                $checkedInSeats = [1, 2, 3, 4, 5, 6, 7, 8];
            }
        } else {
            foreach ($reservations as $res) {
                if ($res->seat_number) {
                    $s = (int)$res->seat_number;
                    $seats[] = $s;
                    if ($res->is_checked_in) {
                        $checkedInSeats[] = $s;
                    }
                }
            }
            sort($seats);
            sort($checkedInSeats);
        }

        $tableCheckedInSeats = [];
        $tableAllReservedSeats = [];
        if ($isSeated && $first->table_number) {
            $tableBookings = Reservation::mapV2()
                ->where('table_number', $first->table_number)
                ->get();

            foreach ($tableBookings as $tb) {
                if ($tb->is_full_table) {
                    $tableAllReservedSeats = [1, 2, 3, 4, 5, 6, 7, 8];
                    if ($tb->is_checked_in) {
                        $tableCheckedInSeats = [1, 2, 3, 4, 5, 6, 7, 8];
                    }
                } elseif ($tb->seat_number) {
                    $tableAllReservedSeats[] = (int)$tb->seat_number;
                    if ($tb->is_checked_in) {
                        $tableCheckedInSeats[] = (int)$tb->seat_number;
                    }
                }
            }
            $tableAllReservedSeats = array_values(array_unique($tableAllReservedSeats));
            $tableCheckedInSeats = array_values(array_unique($tableCheckedInSeats));
        }

        $totalTickets = $isFullTable ? 8 : $reservations->count();
        $checkedInCount = $isFullTable
            ? ($first->is_checked_in ? 8 : 0)
            : $reservations->where('is_checked_in', true)->count();
        $remainingCount = max(0, $totalTickets - $checkedInCount);
        $allCheckedIn = $checkedInCount >= $totalTickets && $totalTickets > 0;

        $latestCheckedIn = $reservations->whereNotNull('checked_in_at')->sortByDesc('checked_in_at')->first();
        $tableNum = $first->table_number ? (int)$first->table_number : null;

        return [
            'name' => $first->name,
            'email' => $first->email,
            'ticket_number' => $first->ticket_number,
            'category' => $category,
            'is_seated' => $isSeated,
            'side' => strtolower($first->side ?? 'center'),
            'table_number' => $tableNum,
            'row_number' => $tableNum ? (int)ceil($tableNum / 10) : null,
            'col_number' => $tableNum ? ((($tableNum - 1) % 10) + 1) : null,
            'table_label' => $tableNum ? "Table {$tableNum}" : 'N/A',
            'is_full_table' => $isFullTable,
            'is_temporary' => (bool)$first->is_temporary,
            'seats' => $seats,
            'checked_in_seats' => $checkedInSeats,
            'table_checked_in_seats' => $tableCheckedInSeats,
            'table_reserved_seats' => $tableAllReservedSeats,
            'total_tickets' => $totalTickets,
            'checked_in_tickets' => $checkedInCount,
            'remaining_tickets' => $remainingCount,
            'all_checked_in' => $allCheckedIn,
            'checked_in_at' => $latestCheckedIn && $latestCheckedIn->checked_in_at
                ? $latestCheckedIn->checked_in_at->format('Y-m-d H:i:s')
                : null,
            'items' => $reservations->map(fn($r) => [
                'id' => $r->id,
                'seat_number' => $r->seat_number,
                'is_full_table' => (bool)$r->is_full_table,
                'is_checked_in' => (bool)$r->is_checked_in,
                'checked_in_at' => $r->checked_in_at ? $r->checked_in_at->format('H:i:s') : null,
            ])->values()->all(),
        ];
    }

    public function calculateStats(): array
    {
        $permScope = function ($q) {
            $q->where('is_temporary', false)->orWhereNull('is_temporary');
        };

        // Platinum: Tables 1-60 (60 tables * 8 seats = 480 seats)
        $platinumFullTables = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', true)->where($permScope)->count();
        $platinumIndividual = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', false)->where($permScope)->count();
        $platinumSold = ($platinumFullTables * 8) + $platinumIndividual;
        $platinumCapacity = 60 * 8; // 480 seats

        $platinumTempFull = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', true)->where('is_temporary', true)->count();
        $platinumTempInd = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', false)->where('is_temporary', true)->count();
        $platinumTemp = ($platinumTempFull * 8) + $platinumTempInd;

        $platinumCheckFull = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', true)->where('is_checked_in', true)->where($permScope)->count();
        $platinumCheckInd = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', false)->where('is_checked_in', true)->where($permScope)->count();
        $platinumCheckedIn = ($platinumCheckFull * 8) + $platinumCheckInd;

        // Gold: Tables 61-130 (70 tables * 8 seats = 560 seats)
        $goldFullTables = Reservation::mapV2()->where('category', 'gold')->where('is_full_table', true)->where($permScope)->count();
        $goldIndividual = Reservation::mapV2()->where('category', 'gold')->where('is_full_table', false)->where($permScope)->count();
        $goldSold = ($goldFullTables * 8) + $goldIndividual;
        $goldCapacity = 70 * 8; // 560 seats

        $goldTempFull = Reservation::mapV2()->where('category', 'gold')->where('is_full_table', true)->where('is_temporary', true)->count();
        $goldTempInd = Reservation::mapV2()->where('category', 'gold')->where('is_full_table', false)->where('is_temporary', true)->count();
        $goldTemp = ($goldTempFull * 8) + $goldTempInd;

        $goldCheckFull = Reservation::mapV2()->where('category', 'gold')->where('is_full_table', true)->where('is_checked_in', true)->where($permScope)->count();
        $goldCheckInd = Reservation::mapV2()->where('category', 'gold')->where('is_full_table', false)->where('is_checked_in', true)->where($permScope)->count();
        $goldCheckedIn = ($goldCheckFull * 8) + $goldCheckInd;

        // Standing: Left & Right Standing Areas
        $standingSold = Reservation::mapV2()->where('category', 'standing')->where($permScope)->count();
        $standingTemp = Reservation::mapV2()->where('category', 'standing')->where('is_temporary', true)->count();
        $standingCheckedIn = Reservation::mapV2()->where('category', 'standing')->where('is_checked_in', true)->where($permScope)->count();

        $totalSold = $platinumSold + $goldSold + $standingSold;
        $totalTemporary = $platinumTemp + $goldTemp + $standingTemp;
        $totalCheckedIn = $platinumCheckedIn + $goldCheckedIn + $standingCheckedIn;
        $totalRemainingCheckIn = max(0, $totalSold - $totalCheckedIn);

        $totalSeatedCapacity = $platinumCapacity + $goldCapacity; // 1040 seats (130 tables * 8)
        $totalSeatedSold = $platinumSold + $goldSold;
        $totalSeatedPercentage = $totalSeatedCapacity > 0 ? round(($totalSeatedSold / $totalSeatedCapacity) * 100, 1) : 0;

        return [
            'total' => [
                'sold' => $totalSold,
                'temporary' => $totalTemporary,
                'seated_sold' => $totalSeatedSold,
                'capacity' => $totalSeatedCapacity,
                'percentage' => $totalSeatedPercentage,
            ],
            'checkin' => [
                'checked_in' => $totalCheckedIn,
                'remaining' => $totalRemainingCheckIn,
                'platinum' => $platinumCheckedIn,
                'gold' => $goldCheckedIn,
                'standing' => $standingCheckedIn,
                'percentage' => $totalSold > 0 ? round(($totalCheckedIn / $totalSold) * 100, 1) : 0,
            ],
            'platinum' => [
                'sold' => $platinumSold,
                'temporary' => $platinumTemp,
                'checked_in' => $platinumCheckedIn,
                'capacity' => $platinumCapacity,
                'percentage' => $platinumCapacity > 0 ? round(($platinumSold / $platinumCapacity) * 100, 1) : 0,
            ],
            'gold' => [
                'sold' => $goldSold,
                'temporary' => $goldTemp,
                'checked_in' => $goldCheckedIn,
                'capacity' => $goldCapacity,
                'percentage' => $goldCapacity > 0 ? round(($goldSold / $goldCapacity) * 100, 1) : 0,
            ],
            'standing' => [
                'sold' => $standingSold,
                'temporary' => $standingTemp,
                'checked_in' => $standingCheckedIn,
            ],
        ];
    }

    public function calculateCheckInStats(): array
    {
        $s = $this->calculateStats();
        return [
            'total_sold' => $s['total']['sold'],
            'checked_in' => $s['checkin']['checked_in'],
            'remaining' => $s['checkin']['remaining'],
            'percentage' => $s['checkin']['percentage'],
            'platinum_checked_in' => $s['platinum']['checked_in'],
            'platinum_sold' => $s['platinum']['sold'],
            'gold_checked_in' => $s['gold']['checked_in'],
            'gold_sold' => $s['gold']['sold'],
            'standing_checked_in' => $s['standing']['checked_in'],
            'standing_sold' => $s['standing']['sold'],
        ];
    }

    private function extractCodesFromQr(string $rawInput): array
    {
        $orderNo = null;
        $ticketCode = null;
        $rawClean = trim($rawInput);

        if (preg_match('/(eyJ[A-Za-z0-9_-]+)\.[A-Za-z0-9_-]+/', $rawClean, $matches)) {
            $jwtPayloadPart = $matches[1];
            $remainder = strlen($jwtPayloadPart) % 4;
            if ($remainder) {
                $jwtPayloadPart .= str_repeat('=', 4 - $remainder);
            }
            $decodedJson = base64_decode(strtr($jwtPayloadPart, '-_', '+/'), true);
            if ($decodedJson) {
                $data = json_decode($decodedJson, true);
                if (is_array($data)) {
                    $orderNo = $data['o'] ?? $data['order_no'] ?? $data['order'] ?? $data['order_id'] ?? null;
                    $ticketCode = $data['t'] ?? $data['ticket'] ?? $data['ticket_number'] ?? null;
                }
            }
        }

        if (!$orderNo && !$ticketCode && str_starts_with($rawClean, '{')) {
            $data = json_decode($rawClean, true);
            if (is_array($data)) {
                $orderNo = $data['o'] ?? $data['order_no'] ?? $data['order'] ?? $data['order_id'] ?? null;
                $ticketCode = $data['t'] ?? $data['ticket'] ?? $data['ticket_number'] ?? null;
            }
        }

        if (!$orderNo && !$ticketCode && preg_match('/\/(?:find-)?seat\/([^\/?#]+)/i', $rawClean, $matches)) {
            $orderNo = urldecode($matches[1]);
        }

        if (!$orderNo && preg_match('/\b(ORD[-A-Z0-9]+)\b/i', $rawClean, $matches)) {
            $orderNo = $matches[1];
        }

        if (!$orderNo && !$ticketCode) {
            $orderNo = $rawClean;
        }

        return [
            'order_no' => $orderNo ? trim((string)$orderNo) : null,
            'ticket_code' => $ticketCode ? trim((string)$ticketCode) : null,
            'raw_clean' => $rawClean,
        ];
    }

    private function syncAppendToSheet(array $reservations): void
    {
        if (empty($reservations)) {
            return;
        }
        if (function_exists('Illuminate\Support\defer')) {
            \Illuminate\Support\defer(function () use ($reservations) {
                $this->googleSheets->appendReservations($reservations);
            });
        } else {
            $this->googleSheets->appendReservations($reservations);
        }
    }
}

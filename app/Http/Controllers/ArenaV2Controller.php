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
     * Rows 1-5 (Tables 1-50): VIP / Platinum (50 tables * 8 seats = 400 seats)
     * Rows 6-13 (Tables 51-130): Gold (80 tables * 8 seats = 640 seats)
     * Bottom Left & Right: Standing Areas + Center FOH & Entrance
     */
    public function index()
    {
        $stats = $this->calculateStats();
        $recentBookings = Reservation::mapV2()->orderBy('id', 'desc')->take(15)->get();
        $isEditor = auth()->check() && method_exists(auth()->user(), 'isEditor') && auth()->user()->isEditor();

        return view('v2-dashboard', [
            'stats' => $stats,
            'recentBookings' => $recentBookings,
            'isEditor' => $isEditor,
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
            'category' => 'required|in:vip,platinum,gold,standing',
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

        // Tables 1-50 = VIP / Platinum (Rows 1-5), Tables 51-130 = Gold (Rows 6-13)
        $category = $table_number <= 50 ? 'platinum' : 'gold';
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

        // VIP / Platinum: Rows 1-5, Tables 1-50 (50 tables * 8 seats = 400 seats)
        $platinumFullTables = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', true)->where($permScope)->count();
        $platinumIndividual = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', false)->where($permScope)->count();
        $platinumSold = ($platinumFullTables * 8) + $platinumIndividual;
        $platinumCapacity = 50 * 8; // 400 seats

        $platinumTempFull = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', true)->where('is_temporary', true)->count();
        $platinumTempInd = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', false)->where('is_temporary', true)->count();
        $platinumTemp = ($platinumTempFull * 8) + $platinumTempInd;

        $platinumCheckFull = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', true)->where('is_checked_in', true)->where($permScope)->count();
        $platinumCheckInd = Reservation::mapV2()->where('category', 'platinum')->where('is_full_table', false)->where('is_checked_in', true)->where($permScope)->count();
        $platinumCheckedIn = ($platinumCheckFull * 8) + $platinumCheckInd;

        // Gold: Rows 6-13, Tables 51-130 (80 tables * 8 seats = 640 seats)
        $goldFullTables = Reservation::mapV2()->where('category', 'gold')->where('is_full_table', true)->where($permScope)->count();
        $goldIndividual = Reservation::mapV2()->where('category', 'gold')->where('is_full_table', false)->where($permScope)->count();
        $goldSold = ($goldFullTables * 8) + $goldIndividual;
        $goldCapacity = 80 * 8; // 640 seats

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

    /**
     * Editor-Only: List Base Map (V1) bookings so the admin can easily import/assign them to the V2 map.
     */
    public function baseBookings()
    {
        if (!auth()->check() || !method_exists(auth()->user(), 'isEditor') || !auth()->user()->isEditor()) {
            return response()->json([
                'success' => true,
                'base_groups' => [],
            ]);
        }

        $v1List = Reservation::mapV1()
            ->select(['id', 'name', 'email', 'ticket_number', 'category', 'side', 'table_number', 'seat_number', 'is_full_table', 'is_temporary', 'is_checked_in'])
            ->orderBy('id', 'desc')
            ->get();
        $v2List = Reservation::mapV2()
            ->select(['name', 'ticket_number', 'category', 'side', 'table_number', 'seat_number', 'is_full_table'])
            ->get();

        // Map V2 ticket_numbers and guest names to their V2 locations so we can accurately check if already placed on V2
        $v2ByTicket = [];
        $v2ByName = [];
        foreach ($v2List as $v2) {
            $key = strtolower(trim((string)$v2->ticket_number));
            $nameKey = strtolower(trim((string)$v2->name));

            $loc = '';
            if ($v2->category === 'standing') {
                $loc = strtoupper($v2->side ?? 'LEFT') . ' Standing';
            } elseif ($v2->is_full_table) {
                $loc = "Table {$v2->table_number} (Full Table)";
            } else {
                $loc = "Table {$v2->table_number} Seat {$v2->seat_number}";
            }

            if ($key !== '') {
                if (!isset($v2ByTicket[$key])) {
                    $v2ByTicket[$key] = [];
                }
                $v2ByTicket[$key][] = $loc;
            }
            if ($nameKey !== '') {
                if (!isset($v2ByName[$nameKey])) {
                    $v2ByName[$nameKey] = [];
                }
                $v2ByName[$nameKey][] = $loc;
            }
        }

        // Group V1 bookings by Order/Ticket + Table so multi-seat orders can be placed in one action
        $groups = [];
        foreach ($v1List as $b) {
            $groupKey = ($b->ticket_number ?: ('ID-' . $b->id)) . '|' . $b->category . '|' . ($b->side ?? '') . '|' . ($b->table_number ?? '');
            if (!isset($groups[$groupKey])) {
                $v1Loc = $b->category === 'standing'
                    ? ucfirst($b->side ?? 'Center') . ' Standing'
                    : 'Table ' . strtoupper(substr($b->side ?? 'L', 0, 1)) . $b->table_number;

                $tKey = strtolower(trim((string)$b->ticket_number));
                $nKey = strtolower(trim((string)$b->name));
                $v2Locations = [];
                if ($tKey !== '' && isset($v2ByTicket[$tKey])) {
                    $v2Locations = $v2ByTicket[$tKey];
                } elseif ($nKey !== '' && isset($v2ByName[$nKey])) {
                    $v2Locations = $v2ByName[$nKey];
                }
                $v2Locations = array_values(array_unique($v2Locations));

                $groups[$groupKey] = [
                    'group_key' => $groupKey,
                    'primary_id' => $b->id,
                    'ids' => [],
                    'name' => $b->name,
                    'email' => $b->email,
                    'ticket_number' => $b->ticket_number,
                    'category' => $b->category,
                    'side' => $b->side,
                    'v1_table_number' => $b->table_number,
                    'v1_location' => $v1Loc,
                    'is_full_table' => (bool)$b->is_full_table,
                    'is_temporary' => (bool)$b->is_temporary,
                    'is_checked_in' => (bool)$b->is_checked_in,
                    'seats' => [],
                    'seat_count' => 0,
                    'already_on_v2' => count($v2Locations) > 0,
                    'v2_locations' => $v2Locations,
                ];
            } else {
                // If any subsequent seat in the group matches a V2 location, mark as placed
                if (!$groups[$groupKey]['already_on_v2']) {
                    $tKey = strtolower(trim((string)$b->ticket_number));
                    $nKey = strtolower(trim((string)$b->name));
                    if ($tKey !== '' && isset($v2ByTicket[$tKey])) {
                        $groups[$groupKey]['already_on_v2'] = true;
                        $groups[$groupKey]['v2_locations'] = array_values(array_unique(array_merge($groups[$groupKey]['v2_locations'], $v2ByTicket[$tKey])));
                    } elseif ($nKey !== '' && isset($v2ByName[$nKey])) {
                        $groups[$groupKey]['already_on_v2'] = true;
                        $groups[$groupKey]['v2_locations'] = array_values(array_unique(array_merge($groups[$groupKey]['v2_locations'], $v2ByName[$nKey])));
                    }
                }
            }

            $groups[$groupKey]['ids'][] = $b->id;
            if ($b->is_full_table) {
                $groups[$groupKey]['seats'] = [1, 2, 3, 4, 5, 6, 7, 8];
                $groups[$groupKey]['seat_count'] = 8;
            } else {
                if ($b->seat_number) {
                    $groups[$groupKey]['seats'][] = (int)$b->seat_number;
                    sort($groups[$groupKey]['seats']);
                }
                $groups[$groupKey]['seat_count'] = count($groups[$groupKey]['ids']);
            }
        }

        return response()->json([
            'success' => true,
            'base_groups' => array_values($groups),
        ]);
    }

    /**
     * Editor-Only: Import / Assign a Base Map (V1) booking onto the V2 Seat Map.
     */
    public function importFromBase(Request $request)
    {
        $v1Ids = $request->input('v1_ids', []);
        if (!is_array($v1Ids)) {
            $v1Ids = [$v1Ids];
        }
        if ($request->filled('v1_id')) {
            $v1Ids[] = $request->input('v1_id');
        }
        $v1Ids = array_values(array_unique(array_filter(array_map('intval', $v1Ids))));

        if (empty($v1Ids)) {
            return response()->json(['success' => false, 'message' => 'Please select a Base Map booking to import.'], 422);
        }

        $v1Records = Reservation::mapV1()->whereIn('id', $v1Ids)->orderBy('seat_number', 'asc')->get();
        if ($v1Records->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Base Map booking not found.'], 404);
        }

        $first = $v1Records->first();
        $targetCategory = strtolower((string)$request->input('target_category', $first->category));

        // 1. Standing import
        if ($targetCategory === 'standing' || ($first->category === 'standing' && !$request->filled('table_number'))) {
            $side = strtolower((string)$request->input('side', $first->side ?? 'left'));
            if (!in_array($side, ['left', 'right'], true)) {
                $side = 'left';
            }

            $created = [];
            foreach ($v1Records as $rec) {
                // Duplicate booking onto V2 without touching or deleting original V1 record
                $created[] = Reservation::create([
                    'map_type' => 'v2',
                    'category' => 'standing',
                    'side' => $side,
                    'name' => $rec->name,
                    'email' => $rec->email,
                    'ticket_number' => $rec->ticket_number,
                    'is_full_table' => false,
                    'is_temporary' => (bool)$rec->is_temporary,
                    'is_checked_in' => (bool)$rec->is_checked_in,
                    'checked_in_at' => $rec->checked_in_at,
                ]);
            }

            // Sync permanent standing bookings to V2 Excel sheet
            $perm = array_values(array_filter($created, fn($r) => !$r->is_temporary));
            if (!empty($perm)) {
                try {
                    $this->googleSheets->appendReservations($perm);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Google Sheets append standing import failed: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Imported {$first->name} (" . count($created) . " standing ticket(s)) to V2 " . strtoupper($side) . " Standing Area!",
                'reservations' => $created,
            ]);
        }

        // 2. Seated Table import onto V2 Table (1 - 130)
        $request->validate([
            'table_number' => 'required|integer|min:1|max:130',
        ]);

        $tableNumber = (int)$request->input('table_number');
        $v2Category = $tableNumber <= 50 ? 'platinum' : 'gold';
        $wantFullTable = $request->has('is_full_table')
            ? filter_var($request->input('is_full_table'), FILTER_VALIDATE_BOOLEAN)
            : (bool)$first->is_full_table;

        try {
            $created = DB::transaction(function () use ($request, $v1Records, $first, $tableNumber, $v2Category, $wantFullTable) {
                $existingOnTable = Reservation::mapV2()
                    ->where('table_number', $tableNumber)
                    ->lockForUpdate()
                    ->get();

                if ($existingOnTable->contains(fn($r) => (bool)$r->is_full_table)) {
                    throw new \Exception("Table {$tableNumber} on V2 is already fully reserved.");
                }

                if ($wantFullTable) {
                    if ($existingOnTable->isNotEmpty()) {
                        throw new \Exception("Table {$tableNumber} already has individual seats booked. Choose an empty table for a Full Table import.");
                    }

                    // Create new V2 full table reservation (keep original V1 record intact)
                    $newRes = Reservation::create([
                        'map_type' => 'v2',
                        'category' => $v2Category,
                        'side' => 'center',
                        'table_number' => $tableNumber,
                        'seat_number' => null,
                        'is_full_table' => true,
                        'is_temporary' => (bool)$first->is_temporary,
                        'is_checked_in' => (bool)$first->is_checked_in,
                        'checked_in_at' => $first->checked_in_at,
                        'name' => $first->name,
                        'email' => $first->email,
                        'ticket_number' => $first->ticket_number,
                    ]);

                    return [$newRes];
                }

                // Individual seat(s) import
                $neededCount = $first->is_full_table ? 8 : $v1Records->count();
                $takenSeats = $existingOnTable->pluck('seat_number')->filter()->map(fn($s) => (int)$s)->all();

                $requestedSeats = $request->input('seat_numbers', []);
                if (!is_array($requestedSeats)) {
                    $requestedSeats = [$requestedSeats];
                }
                $requestedSeats = array_values(array_unique(array_filter(array_map('intval', $requestedSeats))));

                if (!empty($requestedSeats)) {
                    foreach ($requestedSeats as $s) {
                        if ($s < 1 || $s > 8) {
                            throw new \Exception("Seat number must be between 1 and 8.");
                        }
                        if (in_array($s, $takenSeats, true)) {
                            throw new \Exception("Seat {$s} on Table {$tableNumber} is already reserved.");
                        }
                    }
                    $targetSeats = $requestedSeats;
                } else {
                    // Try preserving original seat numbers if free, otherwise pick first available seats on Table
                    $origSeats = $v1Records->pluck('seat_number')->filter()->map(fn($s) => (int)$s)->values()->all();
                    $canUseOrig = count($origSeats) === $neededCount && count(array_intersect($origSeats, $takenSeats)) === 0;

                    if ($canUseOrig) {
                        $targetSeats = $origSeats;
                    } else {
                        $availableSeats = array_values(array_diff([1, 2, 3, 4, 5, 6, 7, 8], $takenSeats));
                        if (count($availableSeats) < $neededCount) {
                            throw new \Exception("Table {$tableNumber} only has " . count($availableSeats) . " available seat(s), but {$neededCount} seat(s) are needed.");
                        }
                        $targetSeats = array_slice($availableSeats, 0, $neededCount);
                    }
                }

                sort($targetSeats);
                $createdRows = [];
                foreach ($targetSeats as $idx => $seatNum) {
                    $src = $v1Records[$idx] ?? $first;
                    // Create new V2 reservation (keep original V1 record intact)
                    $createdRows[] = Reservation::create([
                        'map_type' => 'v2',
                        'category' => $v2Category,
                        'side' => 'center',
                        'table_number' => $tableNumber,
                        'seat_number' => $seatNum,
                        'is_full_table' => false,
                        'is_temporary' => (bool)$src->is_temporary,
                        'is_checked_in' => (bool)$src->is_checked_in,
                        'checked_in_at' => $src->checked_in_at,
                        'name' => $src->name,
                        'email' => $src->email,
                        'ticket_number' => $src->ticket_number,
                    ]);
                }

                return $createdRows;
            });

            // Sync permanent seated bookings to V2 Excel sheet
            $perm = array_values(array_filter($created, fn($r) => !$r->is_temporary));
            if (!empty($perm)) {
                try {
                    $this->googleSheets->appendReservations($perm);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Google Sheets append seated import failed: " . $e->getMessage());
                }
            }

            $seatDesc = $wantFullTable
                ? 'Full Table (8 Seats)'
                : 'Seat(s) ' . implode(', ', array_map(fn($r) => $r->seat_number, $created));

            return response()->json([
                'success' => true,
                'message' => "Assigned {$first->name} to V2 Table {$tableNumber} ({$seatDesc})!",
                'reservations' => $created,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Editor-Only: Move / Reallocate existing V2 seat(s) or full table to another Table & Seat(s).
     */
    public function moveAllocation(Request $request)
    {
        if (!auth()->check() || !method_exists(auth()->user(), 'isEditor') || !auth()->user()->isEditor()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the Editor account (editor2026.kalabara@gmail.com) can move user seat allocations.',
            ], 403);
        }

        $request->validate([
            'target_table_number' => 'required|integer|min:1|max:130',
        ]);

        $reservationIds = $request->input('reservation_ids', []);
        if (!is_array($reservationIds)) {
            $reservationIds = [$reservationIds];
        }
        if ($request->filled('reservation_id')) {
            $reservationIds[] = $request->input('reservation_id');
        }
        $reservationIds = array_values(array_unique(array_filter(array_map('intval', $reservationIds))));

        if (empty($reservationIds)) {
            return response()->json(['success' => false, 'message' => 'No reservation selected to move.'], 422);
        }

        $targetTable = (int)$request->input('target_table_number');
        $targetCategory = $targetTable <= 50 ? 'platinum' : 'gold';

        try {
            $moved = DB::transaction(function () use ($request, $reservationIds, $targetTable, $targetCategory) {
                $records = Reservation::mapV2()
                    ->whereIn('id', $reservationIds)
                    ->orderBy('seat_number', 'asc')
                    ->lockForUpdate()
                    ->get();

                if ($records->isEmpty()) {
                    throw new \Exception('Reservation not found on V2 map.');
                }

                $first = $records->first();
                $isFullTableMove = (bool)$first->is_full_table && $records->count() === 1;

                // Check existing bookings on the target table (excluding the records being moved)
                $existingOnTarget = Reservation::mapV2()
                    ->where('table_number', $targetTable)
                    ->whereNotIn('id', $reservationIds)
                    ->lockForUpdate()
                    ->get();

                if ($existingOnTarget->contains(fn($r) => (bool)$r->is_full_table)) {
                    throw new \Exception("Target Table {$targetTable} is already fully reserved.");
                }

                if ($isFullTableMove) {
                    if ($existingOnTarget->isNotEmpty()) {
                        throw new \Exception("Target Table {$targetTable} already has individual seats reserved. Cannot move a Full Table there.");
                    }

                    $first->table_number = $targetTable;
                    $first->category = $targetCategory;
                    $first->seat_number = null;
                    $first->is_full_table = true;
                    $first->save();

                    return [$first];
                }

                // Moving 1 or more individual seats
                $takenSeats = $existingOnTarget->pluck('seat_number')->filter()->map(fn($s) => (int)$s)->all();
                $requestedSeats = $request->input('target_seat_numbers', $request->input('target_seat_number', []));
                if (!is_array($requestedSeats)) {
                    $requestedSeats = $requestedSeats === null || $requestedSeats === '' ? [] : [$requestedSeats];
                }
                $requestedSeats = array_values(array_unique(array_filter(array_map('intval', $requestedSeats))));

                $neededCount = $records->count();

                if (!empty($requestedSeats)) {
                    if (count($requestedSeats) !== $neededCount) {
                        throw new \Exception("Please select {$neededCount} target seat(s) on Table {$targetTable}.");
                    }
                    foreach ($requestedSeats as $s) {
                        if ($s < 1 || $s > 8) {
                            throw new \Exception('Seat number must be between 1 and 8.');
                        }
                        if (in_array($s, $takenSeats, true)) {
                            throw new \Exception("Seat {$s} on Table {$targetTable} is already occupied.");
                        }
                    }
                    $targetSeats = $requestedSeats;
                } else {
                    // Preserve original seat numbers if free on target table, else pick first available seats
                    $origSeats = $records->pluck('seat_number')->filter()->map(fn($s) => (int)$s)->values()->all();
                    $canKeepSameSeats = count($origSeats) === $neededCount && count(array_intersect($origSeats, $takenSeats)) === 0;
                    if ($canKeepSameSeats) {
                        $targetSeats = $origSeats;
                    } else {
                        $avail = array_values(array_diff([1, 2, 3, 4, 5, 6, 7, 8], $takenSeats));
                        if (count($avail) < $neededCount) {
                            throw new \Exception("Table {$targetTable} only has " . count($avail) . " free seat(s), cannot move {$neededCount} seat(s) there.");
                        }
                        $targetSeats = array_slice($avail, 0, $neededCount);
                    }
                }

                sort($targetSeats);
                $updated = [];
                foreach ($records->values() as $idx => $rec) {
                    $rec->table_number = $targetTable;
                    $rec->category = $targetCategory;
                    $rec->seat_number = $targetSeats[$idx];
                    $rec->is_full_table = false;
                    $rec->save();
                    $updated[] = $rec;
                }

                return $updated;
            });

            // Sync permanent moved bookings to Google Sheets in a single fast batch
            $permanentMoved = array_values(array_filter($moved, fn($m) => !$m->is_temporary));
            if (!empty($permanentMoved)) {
                try {
                    $this->googleSheets->updateReservationsBatch($permanentMoved);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Google Sheets batch update on move failed: " . $e->getMessage());
                }
            }

            $firstMoved = $moved[0];
            $desc = $firstMoved->is_full_table
                ? "Table {$targetTable} (Full Table)"
                : "Table {$targetTable} (Seat " . implode(', ', array_map(fn($r) => $r->seat_number, $moved)) . ")";

            return response()->json([
                'success' => true,
                'message' => "Moved {$firstMoved->name} to {$desc}!",
                'reservations' => $moved,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
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

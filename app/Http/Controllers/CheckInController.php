<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    /**
     * Show the Admin QR Scanner & Check-In Seat Locator page.
     */
    public function index(Request $request)
    {
        $stats = $this->calculateCheckInStats();
        $initialQuery = trim($request->query('code', $request->query('order', '')));

        return view('scanner', [
            'stats' => $stats,
            'initialQuery' => $initialQuery,
        ]);
    }

    /**
     * Parse QR payload / Order No / Ticket Code and look up seat location.
     */
    public function lookup(Request $request)
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
        $matchedCode = null;

        // 1. Exact or case-insensitive match against ticket_number
        foreach ($candidates as $candidate) {
            $found = Reservation::whereRaw('LOWER(TRIM(ticket_number)) = ?', [strtolower(trim($candidate))])
                ->orderBy('seat_number', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($found->isNotEmpty()) {
                $reservations = $found;
                $matchedCode = $candidate;
                break;
            }
        }

        // 2. Fallback: partial match if order number has prefix/suffix
        if ($reservations->isEmpty()) {
            foreach ($candidates as $candidate) {
                if (strlen($candidate) >= 5) {
                    $found = Reservation::whereRaw('LOWER(ticket_number) LIKE ?', ['%' . strtolower(trim($candidate)) . '%'])
                        ->orderBy('seat_number', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    if ($found->isNotEmpty()) {
                        $reservations = $found;
                        $matchedCode = $found->first()->ticket_number;
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
     * Mark reservation(s) as Checked-In (or undo check-in).
     */
    public function markCheckIn(Request $request)
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

        $query = Reservation::query();
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

        // Reload the full group for this order/ticket number
        $primaryTicket = $targets->first()->ticket_number;
        $groupReservations = Reservation::whereRaw('LOWER(TRIM(ticket_number)) = ?', [strtolower(trim($primaryTicket))])
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
     * Extract Order No ("o") and Ticket Code ("t") from QR URL, JWT, JSON, or plain string.
     */
    private function extractCodesFromQr(string $rawInput): array
    {
        $orderNo = null;
        $ticketCode = null;
        $rawClean = trim($rawInput);

        // 1. Check if it contains a JWT token (e.g. https://ticketz.lk/order/verify/eyJ...)
        if (preg_match('/(eyJ[A-Za-z0-9_-]+)\.[A-Za-z0-9_-]+/', $rawClean, $matches)) {
            $jwtPayloadPart = $matches[1];
            $decodedJson = $this->base64UrlDecode($jwtPayloadPart);
            if ($decodedJson) {
                $data = json_decode($decodedJson, true);
                if (is_array($data)) {
                    $orderNo = $data['o'] ?? $data['order_no'] ?? $data['order'] ?? $data['order_id'] ?? null;
                    $ticketCode = $data['t'] ?? $data['ticket'] ?? $data['ticket_number'] ?? null;
                }
            }
        }

        // 2. Check if raw input is direct JSON
        if (!$orderNo && !$ticketCode && str_starts_with($rawClean, '{')) {
            $data = json_decode($rawClean, true);
            if (is_array($data)) {
                $orderNo = $data['o'] ?? $data['order_no'] ?? $data['order'] ?? $data['order_id'] ?? null;
                $ticketCode = $data['t'] ?? $data['ticket'] ?? $data['ticket_number'] ?? null;
            }
        }

        // 3. Check if raw input is a /seat/{ticket} or /find-seat/{ticket} URL
        if (!$orderNo && !$ticketCode && preg_match('/\/(?:find-)?seat\/([^\/?#]+)/i', $rawClean, $matches)) {
            $orderNo = urldecode($matches[1]);
        }

        // 4. Check if URL has query param ?order=... or ?ticket=... or ?code=...
        if (!$orderNo && !$ticketCode && str_contains($rawClean, '?')) {
            $queryPart = parse_url($rawClean, PHP_URL_QUERY);
            if ($queryPart) {
                parse_str($queryPart, $queryParams);
                $orderNo = $queryParams['order'] ?? $queryParams['o'] ?? $queryParams['ticket'] ?? $queryParams['code'] ?? null;
            }
        }

        // 5. Fallback: extract ORD... pattern if present in multiline text
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

    private function base64UrlDecode(string $input): ?string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($input, '-_', '+/'), true);
        return $decoded !== false ? $decoded : null;
    }

    /**
     * Format a group of Reservation records sharing an Order / Ticket Number.
     */
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

        // Also fetch ALL checked-in seats on the same table (even from other orders) so the table ring shows lock icons on all checked-in seats
        $tableCheckedInSeats = [];
        $tableAllReservedSeats = [];
        if ($isSeated && $first->table_number) {
            $tableBookings = Reservation::where('category', $first->category)
                ->where('side', $first->side)
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

        return [
            'name' => $first->name,
            'email' => $first->email,
            'ticket_number' => $first->ticket_number,
            'category' => $category,
            'is_seated' => $isSeated,
            'side' => strtolower($first->side ?? 'center'),
            'table_number' => $first->table_number ? (int)$first->table_number : null,
            'table_label' => $first->table_number ? (strtoupper(substr($first->side, 0, 1)) . $first->table_number) : 'N/A',
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
            ' partially_checked_in' => $checkedInCount > 0 && !$allCheckedIn,
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

    /**
     * Calculate global check-in counts and remaining countdown.
     */
    public function calculateCheckInStats(): array
    {
        $permScope = function ($q) {
            $q->where('is_temporary', false)->orWhereNull('is_temporary');
        };

        // Total Permanent Sold
        $platFullSold = Reservation::where('category', 'platinum')->where('is_full_table', true)->where($permScope)->count();
        $platIndSold = Reservation::where('category', 'platinum')->where('is_full_table', false)->where($permScope)->count();
        $platSold = ($platFullSold * 8) + $platIndSold;

        $goldFullSold = Reservation::where('category', 'gold')->where('is_full_table', true)->where($permScope)->count();
        $goldIndSold = Reservation::where('category', 'gold')->where('is_full_table', false)->where($permScope)->count();
        $goldSold = ($goldFullSold * 8) + $goldIndSold;

        $standingSold = Reservation::where('category', 'standing')->where($permScope)->count();
        $totalSold = $platSold + $goldSold + $standingSold;

        // Checked-In Counts
        $platFullIn = Reservation::where('category', 'platinum')->where('is_full_table', true)->where('is_checked_in', true)->where($permScope)->count();
        $platIndIn = Reservation::where('category', 'platinum')->where('is_full_table', false)->where('is_checked_in', true)->where($permScope)->count();
        $platCheckedIn = ($platFullIn * 8) + $platIndIn;

        $goldFullIn = Reservation::where('category', 'gold')->where('is_full_table', true)->where('is_checked_in', true)->where($permScope)->count();
        $goldIndIn = Reservation::where('category', 'gold')->where('is_full_table', false)->where('is_checked_in', true)->where($permScope)->count();
        $goldCheckedIn = ($goldFullIn * 8) + $goldIndIn;

        $standingCheckedIn = Reservation::where('category', 'standing')->where('is_checked_in', true)->where($permScope)->count();

        $totalCheckedIn = $platCheckedIn + $goldCheckedIn + $standingCheckedIn;
        $totalRemaining = max(0, $totalSold - $totalCheckedIn);

        return [
            'total_sold' => $totalSold,
            'checked_in' => $totalCheckedIn,
            'remaining' => $totalRemaining,
            'percentage' => $totalSold > 0 ? round(($totalCheckedIn / $totalSold) * 100, 1) : 0,
            'platinum_checked_in' => $platCheckedIn,
            'platinum_sold' => $platSold,
            'gold_checked_in' => $goldCheckedIn,
            'gold_sold' => $goldSold,
            'standing_checked_in' => $standingCheckedIn,
            'standing_sold' => $standingSold,
        ];
    }
}


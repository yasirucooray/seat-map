<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class CustomerSeatController extends Controller
{
    /**
     * Display the anonymous customer seat locator.
     * 
     * Privacy & Security Guarantees:
     * - ONLY the customer's own booking is loaded.
     * - ALL other tables are completely locked and generic (NO reserved/available status shown).
     * - ZERO sales, revenue, capacity, or third-party guest data is transmitted.
     */
    public function show(Request $request, $ticket = null)
    {
        $ticketQuery = trim($ticket ?? $request->query('ticket', $request->query('code', '')));

        $customerBookings = collect();
        $customerInfo = null;

        if (!empty($ticketQuery)) {
            // Find by exact ticket number
            $customerBookings = Reservation::where('ticket_number', $ticketQuery)->get();

            // Fallback case-insensitive
            if ($customerBookings->isEmpty()) {
                $customerBookings = Reservation::whereRaw('LOWER(ticket_number) = ?', [strtolower($ticketQuery)])->get();
            }

            if ($customerBookings->isNotEmpty()) {
                $first = $customerBookings->first();
                $seats = $customerBookings->pluck('seat_number')->filter()->map(fn($s) => (int)$s)->values()->all();
                sort($seats);

                $customerInfo = [
                    'name' => $first->name,
                    'ticket_number' => $first->ticket_number,
                    'category' => strtolower($first->category),
                    'side' => strtolower($first->side ?? 'center'),
                    'table_number' => $first->table_number ? (int)$first->table_number : null,
                    'is_full_table' => (bool)$first->is_full_table,
                    'seats' => $seats,
                    'seat_count' => $first->is_full_table ? 8 : max(count($seats), $customerBookings->count()),
                ];
            }
        }

        return view('customer-seat', [
            'customerInfo' => $customerInfo,
            'ticketQuery' => $ticketQuery,
        ]);
    }
}

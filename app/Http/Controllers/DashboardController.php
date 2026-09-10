<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Platinum calculations (54 tables each side, 8 seats per table)
        $platinumFullTables = Reservation::where('category', 'platinum')->where('is_full_table', true)->count();
        $platinumIndividual = Reservation::where('category', 'platinum')->where('is_full_table', false)->count();
        $platinumSold = ($platinumFullTables * 8) + $platinumIndividual;
        $platinumCapacity = 108 * 8; // 108 tables total * 8 seats

        // Gold calculations (72 tables each side, 8 seats per table)
        $goldFullTables = Reservation::where('category', 'gold')->where('is_full_table', true)->count();
        $goldIndividual = Reservation::where('category', 'gold')->where('is_full_table', false)->count();
        $goldSold = ($goldFullTables * 8) + $goldIndividual;
        $goldCapacity = 144 * 8; // 144 tables total * 8 seats

        // Standing calculations
        $standingSold = Reservation::where('category', 'standing')->count();

        // Recent bookings (latest 10)
        $recentBookings = Reservation::orderBy('id', 'desc')->take(10)->get();

        return view('dashboard', [
            'stats' => [
                'platinum' => [
                    'sold' => $platinumSold,
                    'capacity' => $platinumCapacity,
                    'percentage' => $platinumCapacity > 0 ? round(($platinumSold / $platinumCapacity) * 100, 1) : 0
                ],
                'gold' => [
                    'sold' => $goldSold,
                    'capacity' => $goldCapacity,
                    'percentage' => $goldCapacity > 0 ? round(($goldSold / $goldCapacity) * 100, 1) : 0
                ],
                'standing' => [
                    'sold' => $standingSold
                ]
            ],
            'recentBookings' => $recentBookings
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Platinum calculations (54 tables each side, 8 seats per table)
        $platinumFullTables = Reservation::where('category', 'platinum')->where('is_full_table', true)->where(function($q) {
            $q->where('is_temporary', false)->orWhereNull('is_temporary');
        })->count();
        $platinumIndividual = Reservation::where('category', 'platinum')->where('is_full_table', false)->where(function($q) {
            $q->where('is_temporary', false)->orWhereNull('is_temporary');
        })->count();
        $platinumSold = ($platinumFullTables * 8) + $platinumIndividual;
        $platinumCapacity = 108 * 8; // 108 tables total * 8 seats

        // Platinum Temporary calculations
        $platinumTempFullTables = Reservation::where('category', 'platinum')->where('is_full_table', true)->where('is_temporary', true)->count();
        $platinumTempIndividual = Reservation::where('category', 'platinum')->where('is_full_table', false)->where('is_temporary', true)->count();
        $platinumTemp = ($platinumTempFullTables * 8) + $platinumTempIndividual;

        // Gold calculations (72 tables each side, 8 seats per table)
        $goldFullTables = Reservation::where('category', 'gold')->where('is_full_table', true)->where(function($q) {
            $q->where('is_temporary', false)->orWhereNull('is_temporary');
        })->count();
        $goldIndividual = Reservation::where('category', 'gold')->where('is_full_table', false)->where(function($q) {
            $q->where('is_temporary', false)->orWhereNull('is_temporary');
        })->count();
        $goldSold = ($goldFullTables * 8) + $goldIndividual;
        $goldCapacity = 144 * 8; // 144 tables total * 8 seats

        // Gold Temporary calculations
        $goldTempFullTables = Reservation::where('category', 'gold')->where('is_full_table', true)->where('is_temporary', true)->count();
        $goldTempIndividual = Reservation::where('category', 'gold')->where('is_full_table', false)->where('is_temporary', true)->count();
        $goldTemp = ($goldTempFullTables * 8) + $goldTempIndividual;

        // Standing calculations
        $standingSold = Reservation::where('category', 'standing')->where(function($q) {
            $q->where('is_temporary', false)->orWhereNull('is_temporary');
        })->count();
        $standingTemp = Reservation::where('category', 'standing')->where('is_temporary', true)->count();

        // Total calculations
        $totalSold = $platinumSold + $goldSold + $standingSold; // Permanent Confirmed
        $totalTemporary = $platinumTemp + $goldTemp + $standingTemp; // Temporary Reserved

        $totalSeatedCapacity = $platinumCapacity + $goldCapacity;
        $totalSeatedSold = $platinumSold + $goldSold;
        $totalSeatedPercentage = $totalSeatedCapacity > 0 ? round(($totalSeatedSold / $totalSeatedCapacity) * 100, 1) : 0;

        // Recent bookings (latest 15)
        $recentBookings = Reservation::orderBy('id', 'desc')->take(15)->get();

        return view('dashboard', [
            'stats' => [
                'total' => [
                    'sold' => $totalSold,
                    'temporary' => $totalTemporary,
                    'seated_sold' => $totalSeatedSold,
                    'capacity' => $totalSeatedCapacity,
                    'percentage' => $totalSeatedPercentage,
                ],
                'platinum' => [
                    'sold' => $platinumSold,
                    'temporary' => $platinumTemp,
                    'capacity' => $platinumCapacity,
                    'percentage' => $platinumCapacity > 0 ? round(($platinumSold / $platinumCapacity) * 100, 1) : 0
                ],
                'gold' => [
                    'sold' => $goldSold,
                    'temporary' => $goldTemp,
                    'capacity' => $goldCapacity,
                    'percentage' => $goldCapacity > 0 ? round(($goldSold / $goldCapacity) * 100, 1) : 0
                ],
                'standing' => [
                    'sold' => $standingSold,
                    'temporary' => $standingTemp,
                ],
                'temporary_total' => $totalTemporary,
            ],
            'recentBookings' => $recentBookings
        ]);
    }
}

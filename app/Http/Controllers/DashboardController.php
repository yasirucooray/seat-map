<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $permScope = function ($q) {
            $q->where('is_temporary', false)->orWhereNull('is_temporary');
        };

        // Platinum calculations (54 tables each side, 8 seats per table)
        $platinumFullTables = Reservation::where('category', 'platinum')->where('is_full_table', true)->where($permScope)->count();
        $platinumIndividual = Reservation::where('category', 'platinum')->where('is_full_table', false)->where($permScope)->count();
        $platinumSold = ($platinumFullTables * 8) + $platinumIndividual;
        $platinumCapacity = 108 * 8; // 108 tables total * 8 seats

        // Platinum Temporary calculations
        $platinumTempFullTables = Reservation::where('category', 'platinum')->where('is_full_table', true)->where('is_temporary', true)->count();
        $platinumTempIndividual = Reservation::where('category', 'platinum')->where('is_full_table', false)->where('is_temporary', true)->count();
        $platinumTemp = ($platinumTempFullTables * 8) + $platinumTempIndividual;

        // Platinum Checked-In
        $platinumCheckedInFull = Reservation::where('category', 'platinum')->where('is_full_table', true)->where('is_checked_in', true)->where($permScope)->count();
        $platinumCheckedInInd = Reservation::where('category', 'platinum')->where('is_full_table', false)->where('is_checked_in', true)->where($permScope)->count();
        $platinumCheckedIn = ($platinumCheckedInFull * 8) + $platinumCheckedInInd;

        // Gold calculations (72 tables each side, 8 seats per table)
        $goldFullTables = Reservation::where('category', 'gold')->where('is_full_table', true)->where($permScope)->count();
        $goldIndividual = Reservation::where('category', 'gold')->where('is_full_table', false)->where($permScope)->count();
        $goldSold = ($goldFullTables * 8) + $goldIndividual;
        $goldCapacity = 144 * 8; // 144 tables total * 8 seats

        // Gold Temporary calculations
        $goldTempFullTables = Reservation::where('category', 'gold')->where('is_full_table', true)->where('is_temporary', true)->count();
        $goldTempIndividual = Reservation::where('category', 'gold')->where('is_full_table', false)->where('is_temporary', true)->count();
        $goldTemp = ($goldTempFullTables * 8) + $goldTempIndividual;

        // Gold Checked-In
        $goldCheckedInFull = Reservation::where('category', 'gold')->where('is_full_table', true)->where('is_checked_in', true)->where($permScope)->count();
        $goldCheckedInInd = Reservation::where('category', 'gold')->where('is_full_table', false)->where('is_checked_in', true)->where($permScope)->count();
        $goldCheckedIn = ($goldCheckedInFull * 8) + $goldCheckedInInd;

        // Standing calculations
        $standingSold = Reservation::where('category', 'standing')->where($permScope)->count();
        $standingTemp = Reservation::where('category', 'standing')->where('is_temporary', true)->count();
        $standingCheckedIn = Reservation::where('category', 'standing')->where('is_checked_in', true)->where($permScope)->count();

        // Total calculations
        $totalSold = $platinumSold + $goldSold + $standingSold; // Permanent Confirmed
        $totalTemporary = $platinumTemp + $goldTemp + $standingTemp; // Temporary Reserved
        $totalCheckedIn = $platinumCheckedIn + $goldCheckedIn + $standingCheckedIn;
        $totalRemainingCheckIn = max(0, $totalSold - $totalCheckedIn);

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
                    'percentage' => $platinumCapacity > 0 ? round(($platinumSold / $platinumCapacity) * 100, 1) : 0
                ],
                'gold' => [
                    'sold' => $goldSold,
                    'temporary' => $goldTemp,
                    'checked_in' => $goldCheckedIn,
                    'capacity' => $goldCapacity,
                    'percentage' => $goldCapacity > 0 ? round(($goldSold / $goldCapacity) * 100, 1) : 0
                ],
                'standing' => [
                    'sold' => $standingSold,
                    'temporary' => $standingTemp,
                    'checked_in' => $standingCheckedIn,
                ],
                'temporary_total' => $totalTemporary,
            ],
            'recentBookings' => $recentBookings
        ]);
    }
}

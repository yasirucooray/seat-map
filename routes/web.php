<?php

use App\Http\Controllers\ArenaV2Controller;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Anonymous Customer Seat Locator (Public, 100% Private, Zero Admin Details)
Route::get('/seat/{ticket?}', [\App\Http\Controllers\CustomerSeatController::class, 'show'])->name('customer.seat');
Route::get('/find-seat/{ticket?}', [\App\Http\Controllers\CustomerSeatController::class, 'show']);

// V2 Anonymous Customer Seat Locator (13x10 Stage Seat Map - 66 FT x 115 FT)
Route::get('/v2/seat/{ticket?}', [ArenaV2Controller::class, 'customerSeat'])->name('v2.customer.seat');
Route::get('/v2/find-seat/{ticket?}', [ArenaV2Controller::class, 'customerSeat']);

Route::get('/', function () {
    return redirect()->route('v2.dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin QR Scanner & Check-In Seat Locator (Base Map V1)
    Route::get('/scanner', [CheckInController::class, 'index'])->name('scanner.index');
    Route::get('/check-in', [CheckInController::class, 'index'])->name('checkin.index');
    Route::post('/api/check-in/lookup', [CheckInController::class, 'lookup'])->name('checkin.lookup');
    Route::post('/api/check-in/mark', [CheckInController::class, 'markCheckIn'])->name('checkin.mark');

    // API Routes for booking management (Base Map V1)
    Route::get('/api/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('/api/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::put('/api/bookings/{id}', [BookingController::class, 'update'])->name('bookings.update');
    Route::delete('/api/bookings/{id}', [BookingController::class, 'destroy'])->name('bookings.destroy');

    // =========================================================================
    // V2 SEAT MAP (13x10 Stage Seat Map - 66 FT x 115 FT + Standing + FOH)
    // =========================================================================
    Route::get('/v2/dashboard', [ArenaV2Controller::class, 'index'])->name('v2.dashboard');
    Route::get('/arena-v2', [ArenaV2Controller::class, 'index'])->name('arena.v2');
    Route::get('/new-map', [ArenaV2Controller::class, 'index'])->name('new.map');

    Route::get('/v2/scanner', [ArenaV2Controller::class, 'scanner'])->name('v2.scanner');
    Route::get('/v2/check-in', [ArenaV2Controller::class, 'scanner'])->name('v2.checkin.index');
    Route::post('/api/v2/check-in/lookup', [ArenaV2Controller::class, 'checkInLookup'])->name('v2.checkin.lookup');
    Route::post('/api/v2/check-in/mark', [ArenaV2Controller::class, 'checkInMark'])->name('v2.checkin.mark');

    Route::get('/api/v2/bookings', [ArenaV2Controller::class, 'bookingsIndex'])->name('v2.bookings.index');
    Route::post('/api/v2/bookings', [ArenaV2Controller::class, 'bookingsStore'])->name('v2.bookings.store');
    Route::put('/api/v2/bookings/{id}', [ArenaV2Controller::class, 'bookingsUpdate'])->name('v2.bookings.update');
    Route::delete('/api/v2/bookings/{id}', [ArenaV2Controller::class, 'bookingsDestroy'])->name('v2.bookings.destroy');

    // Editor-Only: Import Base Map (V1) Bookings to V2 & Move/Reallocate V2 Seats
    Route::get('/api/v2/base-bookings', [ArenaV2Controller::class, 'baseBookings'])->name('v2.base.bookings');
    Route::post('/api/v2/import-from-base', [ArenaV2Controller::class, 'importFromBase'])->name('v2.import.base');
    Route::post('/api/v2/move-allocation', [ArenaV2Controller::class, 'moveAllocation'])->name('v2.move.allocation');
});

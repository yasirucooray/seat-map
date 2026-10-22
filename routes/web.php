<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Anonymous Customer Seat Locator (Public, 100% Private, Zero Admin Details)
Route::get('/seat/{ticket?}', [\App\Http\Controllers\CustomerSeatController::class, 'show'])->name('customer.seat');
Route::get('/find-seat/{ticket?}', [\App\Http\Controllers\CustomerSeatController::class, 'show']);

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // API Routes for booking management
    Route::get('/api/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('/api/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::put('/api/bookings/{id}', [BookingController::class, 'update'])->name('bookings.update');
    Route::delete('/api/bookings/{id}', [BookingController::class, 'destroy'])->name('bookings.destroy');
});

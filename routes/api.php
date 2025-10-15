<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\Admin\{
    CinemaController,
    AuditoriumController,
    MovieController,
    SeatController,
    ShowtimeController,
    BookingController as AdminBookingController
};
use App\Http\Controllers\Api\Customer\{
    MovieController as CustomerMovieController,
    ShowtimeController as CustomerShowtimeController,
    BookingController as CustomerBookingController
};

// Public Routes
Route::get('movies', [CustomerMovieController::class, 'index']);
Route::get('movies/{movie}', [CustomerMovieController::class, 'show']);
Route::get('showtimes', [CustomerShowtimeController::class, 'index']);
Route::get('showtimes/{id}', [CustomerShowtimeController::class, 'show']);

// Admin Routes
Route::prefix('admin')->group(function () {
    Route::post('register', [AdminAuthController::class, 'register']);
    Route::post('login', [AdminAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('profile', fn (Request $r) => $r->user());
        Route::get('showtimes/{id}/available-seats', [SeatController::class, 'available']);
        Route::post('lock-seats', [SeatController::class, 'lockSeats']);
        Route::apiResource('cinemas', CinemaController::class);
        Route::apiResource('auditoriums', AuditoriumController::class);
        Route::apiResource('movies', MovieController::class);
        Route::apiResource('showtimes', ShowtimeController::class);
        Route::apiResource('bookings', AdminBookingController::class)->only(['index', 'show', 'update']);
    });
});

// Customer Routes
Route::prefix('customer')->group(function () {
    Route::post('register', [CustomerAuthController::class, 'register']);
    Route::post('login', [CustomerAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
        Route::post('logout', [CustomerAuthController::class, 'logout']);
        Route::get('profile', fn (Request $r) => $r->user());
        Route::post('bookings', [CustomerBookingController::class, 'store']);
        Route::get('bookings', [CustomerBookingController::class, 'index']);
        Route::get('bookings/{id}', [CustomerBookingController::class, 'show']);
        Route::patch('bookings/{id}/cancel', [CustomerBookingController::class, 'cancel']);
    });
});

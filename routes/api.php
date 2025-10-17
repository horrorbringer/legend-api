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

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Public movie and showtime browsing
Route::get('movies', [CustomerMovieController::class, 'index']);
Route::get('movies/{movie}', [CustomerMovieController::class, 'show']);
Route::get('showtimes', [CustomerShowtimeController::class, 'index']);
Route::get('showtimes/{id}', [CustomerShowtimeController::class, 'show']);

// KHQR Webhook (must be public for Bakong to call)
Route::post('webhooks/khqr', [CustomerBookingController::class, 'khqrWebhook']);

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {
    // Admin auth (public)
    Route::post('register', [AdminAuthController::class, 'register']);
    Route::post('login', [AdminAuthController::class, 'login']);

    // Protected admin routes
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('profile', fn(Request $r) => $r->user());

        // Dashboard statistics
        Route::get('dashboard/stats', [App\Http\Controllers\Api\Admin\DashboardController::class, 'getStats']);

        // Seat management
        Route::get('showtimes/{id}/available-seats', [SeatController::class, 'available']);
        Route::post('lock-seats', [SeatController::class, 'lockSeats']);

        // Resource controllers
        Route::apiResource('cinemas', CinemaController::class);
        Route::apiResource('auditoriums', AuditoriumController::class);
        Route::apiResource('movies', MovieController::class);
        Route::apiResource('showtimes', ShowtimeController::class);
        Route::apiResource('bookings', AdminBookingController::class)->only(['index', 'show', 'update']);
    });
});

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

Route::prefix('customer')->group(function () {
    // Customer auth (public)
    Route::post('register', [CustomerAuthController::class, 'register']);
    Route::post('login', [CustomerAuthController::class, 'login']);

    // Protected customer routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [CustomerAuthController::class, 'logout']);
        Route::get('profile', fn(Request $r) => $r->user());
    });
});

/*
|--------------------------------------------------------------------------
| Booking Routes (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('bookings')->group(function () {
    // List user's bookings
    Route::get('/', [CustomerBookingController::class, 'getUserBookings']);

    // Create new booking
    Route::post('/', [CustomerBookingController::class, 'store']);

    // Get booking details
    Route::get('/{id}', [CustomerBookingController::class, 'show']);

    // Generate KHQR code for payment
    Route::post('/{id}/khqr/generate', [CustomerBookingController::class, 'generateKHQR']);

    // Check payment status
    Route::get('/{id}/check-payment', [CustomerBookingController::class, 'checkPaymentStatus']);

    // Cancel booking
    Route::post('/{id}/cancel', [CustomerBookingController::class, 'cancel']);
});


// Token verification endpoint
Route::get('/verify-token', [CustomerAuthController::class, 'verifyToken'])->middleware('auth:sanctum');

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Movie;
use App\Models\Cinema;
use App\Models\Showtime;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Calculate growth percentage between new and old values
     */
    private function calculateGrowth(float $new, float $old): array
    {
        if ($old == 0) {
            return [
                'value' => $new > 0 ? 100 : 0,
                'type' => $new > 0 ? 'increase' : 'neutral'
            ];
        }

        $change = (($new - $old) / $old) * 100;

        return [
            'value' => abs(round($change, 1)),
            'type' => $change >= 0 ? 'increase' : 'decrease'
        ];
    }
    /**
     * Get dashboard statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            // Get today's date range
            $today = Carbon::today();
            $tomorrow = $today->copy()->addDay();
            $monthStart = $today->copy()->startOfMonth();
            $lastMonthStart = $today->copy()->subMonth()->startOfMonth();
            $lastMonthEnd = $monthStart->copy()->subDay();

            // Get user statistics
            $totalUsers = User::where('role', 'customer')->count();

            // Get movie statistics
            $movieStats = Movie::select([
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN status = "now_showing" THEN 1 END) as now_showing'),
                DB::raw('COUNT(CASE WHEN status = "upcoming" THEN 1 END) as upcoming')
            ])->first();

            // Get cinema and auditorium counts
            $totalCinemas = Cinema::count();
            $totalAuditoriums = Cinema::join('auditoriums', 'cinemas.id', '=', 'auditoriums.cinema_id')
                ->count();

            // Get showtime statistics
            $showtimeStats = Showtime::select([
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN start_time > NOW() THEN 1 END) as upcoming'),
                DB::raw('COUNT(CASE WHEN start_time <= NOW() AND DATE_ADD(start_time, INTERVAL 3 HOUR) > NOW() THEN 1 END) as active'),
                DB::raw('COUNT(DISTINCT movie_id) as unique_movies')
            ])->first();

            // Get booking statistics with revenue
            $bookingStats = Booking::select([
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_bookings'),
                DB::raw('COUNT(CASE WHEN status = "paid" THEN 1 END) as completed_bookings'),
                DB::raw('COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_bookings'),
                // Today's revenue
                DB::raw('SUM(CASE WHEN created_at >= ? AND created_at < ? AND status = "paid" THEN total_price ELSE 0 END) as today_revenue'),
                // Yesterday's revenue
                DB::raw('SUM(CASE WHEN created_at >= ? AND created_at < ? AND status = "paid" THEN total_price ELSE 0 END) as yesterday_revenue'),
                // This month's revenue
                DB::raw('SUM(CASE WHEN created_at >= ? AND status = "paid" THEN total_price ELSE 0 END) as monthly_revenue'),
                // Last month's revenue
                DB::raw('SUM(CASE WHEN created_at >= ? AND created_at < ? AND status = "paid" THEN total_price ELSE 0 END) as last_month_revenue'),
                // Payment method statistics
                DB::raw('COUNT(CASE WHEN payment_method = "khqr" AND status = "paid" THEN 1 END) as khqr_payments'),
                DB::raw('COUNT(CASE WHEN payment_method = "aba" AND status = "paid" THEN 1 END) as aba_payments')
            ])
            ->setBindings([
                $today, $tomorrow,              // Today's range
                $today->copy()->subDay(), $today, // Yesterday's range
                $monthStart,                    // This month start
                $lastMonthStart, $monthStart    // Last month range
            ])
            ->first();            return response()->json([
                // User statistics
                'totalUsers' => $totalUsers,

                // Movie statistics
                'totalMovies' => $movieStats->total ?? 0,
                'nowShowingMovies' => $movieStats->now_showing ?? 0,
                'upcomingMovies' => $movieStats->upcoming ?? 0,

                // Cinema statistics
                'totalCinemas' => $totalCinemas,
                'totalAuditoriums' => $totalAuditoriums,

                // Showtime statistics
                'totalShowtimes' => $showtimeStats->total ?? 0,
                'activeShowtimes' => $showtimeStats->active ?? 0,
                'upcomingShowtimes' => $showtimeStats->upcoming ?? 0,
                'uniqueMoviesShowing' => $showtimeStats->unique_movies ?? 0,

                // Booking statistics
                'bookings' => [
                    'total' => $bookingStats->total_bookings ?? 0,
                    'pending' => $bookingStats->pending_bookings ?? 0,
                    'completed' => $bookingStats->completed_bookings ?? 0,
                    'cancelled' => $bookingStats->cancelled_bookings ?? 0
                ],

                // Payment statistics
                'payments' => [
                    'khqr' => $bookingStats->khqr_payments ?? 0,
                    'aba' => $bookingStats->aba_payments ?? 0,
                ],

                // Revenue statistics
                'revenue' => [
                    'today' => [
                        'amount' => number_format($bookingStats->today_revenue ?? 0, 2),
                        'growth' => $this->calculateGrowth(
                            $bookingStats->today_revenue ?? 0,
                            $bookingStats->yesterday_revenue ?? 0
                        )
                    ],
                    'monthly' => [
                        'amount' => number_format($bookingStats->monthly_revenue ?? 0, 2),
                        'growth' => $this->calculateGrowth(
                            $bookingStats->monthly_revenue ?? 0,
                            $bookingStats->last_month_revenue ?? 0
                        )
                    ]
                ],

                'lastUpdated' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard stats error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to fetch dashboard statistics'
            ], 500);
        }
    }
}

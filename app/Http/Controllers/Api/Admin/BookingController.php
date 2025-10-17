<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, Log};
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'showtime.movie', 'showtime.auditorium.cinema', 'bookingSeats.seat']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Search by customer name or email
        if ($request->has('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $bookings = $query->latest()->paginate($request->per_page ?? 10);

        // Add summary statistics
        $summary = [
            'total_bookings' => $bookings->total(),
            'total_revenue' => Booking::where('status', 'paid')->sum('total_price'),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'payment_methods' => DB::table('bookings')
                ->where('status', 'paid')
                ->select('payment_method', DB::raw('count(*) as count'))
                ->groupBy('payment_method')
                ->get()
        ];

        return response()->json([
            'bookings' => $bookings,
            'summary' => $summary
        ]);
    }

    public function show($id)
    {
        $booking = Booking::with([
            'user',
            'showtime.movie',
            'showtime.auditorium.cinema',
            'bookingSeats.seat'
        ])->findOrFail($id);

        // Add booking history
        $history = $booking->audits()->with('user')->get();

        return response()->json([
            'booking' => $booking,
            'history' => $history
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,cancelled',
            'admin_notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $booking = Booking::findOrFail($id);

            // Record old status for history
            $oldStatus = $booking->status;

            $booking->update([
                'status' => $request->status,
                'admin_notes' => $request->admin_notes,
                'updated_by' => Auth::id()
            ]);

            // Log the status change
            Log::info('Booking status updated', [
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'notes' => $request->admin_notes,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Booking updated successfully',
                'booking' => $booking->fresh(['user', 'showtime.movie'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get booking statistics
     */
    public function getStatistics(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30));
        $endDate = $request->get('end_date', Carbon::now());

        $stats = [
            'total_revenue' => Booking::where('status', 'paid')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_price'),

            'booking_counts' => Booking::whereBetween('created_at', [$startDate, $endDate])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),

            'payment_methods' => Booking::where('status', 'paid')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('payment_method', DB::raw('count(*) as count'))
                ->groupBy('payment_method')
                ->get(),

            'daily_revenue' => Booking::where('status', 'paid')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total_price) as revenue'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('date')
                ->get()
        ];

        return response()->json($stats);
    }
}

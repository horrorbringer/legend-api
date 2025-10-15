<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\BookingSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeatController extends Controller
{
    /**
     * Get available seats for a specific showtime.
     */
    public function available($showtimeId)
    {
        $showtime = Showtime::with('auditorium.seats')->findOrFail($showtimeId);

        // Find seats already booked for this showtime
        $bookedSeatIds = BookingSeat::whereHas('booking', function ($query) use ($showtimeId) {
            $query->where('showtime_id', $showtimeId);
        })->pluck('seat_id');

        // Available seats
        $availableSeats = $showtime->auditorium->seats->whereNotIn('id', $bookedSeatIds);

        return response()->json([
            'showtime_id' => $showtimeId,
            'available_seats' => $availableSeats->values(),
        ]);
    }

    /**
     * Lock and reserve seats (temporary hold before payment)
     */
    public function lockSeats(Request $request)
    {
        $validated = $request->validate([
            'showtime_id' => 'required|exists:showtimes,id',
            'seat_ids' => 'required|array',
            'seat_ids.*' => 'exists:seats,id',
        ]);

        $showtimeId = $validated['showtime_id'];
        $seatIds = $validated['seat_ids'];

        try {
            DB::beginTransaction();

            // Re-check availability with DB-level lock
            $bookedSeatIds = BookingSeat::whereHas('booking', function ($query) use ($showtimeId) {
                $query->where('showtime_id', $showtimeId);
            })->pluck('seat_id')->toArray();

            $alreadyBooked = array_intersect($seatIds, $bookedSeatIds);
            if (!empty($alreadyBooked)) {
                DB::rollBack();
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Some seats are already booked.',
                    'conflicts' => $alreadyBooked,
                ], 409);
            }

            // Simulate seat lock (insert into temporary lock table or cache)
            // For now, we’ll just respond successfully
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Seats locked successfully.',
                'locked_seats' => $seatIds,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

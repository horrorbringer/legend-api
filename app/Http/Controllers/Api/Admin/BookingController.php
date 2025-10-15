<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['customer', 'showtime.movie', 'showtime.auditorium'])
            ->latest()
            ->paginate(10);

        return response()->json($bookings);
    }

    public function show($id)
    {
        $booking = Booking::with(['customer', 'showtime.movie', 'showtime.auditorium', 'seats'])
            ->findOrFail($id);

        return response()->json($booking);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,cancelled',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update(['status' => $request->status]);

        return response()->json(['message' => 'Booking updated']);
    }
}

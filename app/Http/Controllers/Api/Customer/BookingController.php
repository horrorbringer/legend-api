<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use Illuminate\Http\Request;
use App\Models\{Booking, BookingSeat, Customer, Seat, Showtime};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Info(
 *     title="Cinema Booking API",
 *     version="1.0.0",
 *     description="API for cinema ticket booking system"
 * )
 */
class BookingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/customer/bookings",
     *     summary="Get customer's bookings",
     *     tags={"Customer Booking"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/BookingResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $bookings = Booking::where('customer_id', $request->user()->id)
            ->with(['showtime.movie', 'seats'])
            ->latest()
            ->get();

        return BookingResource::collection($bookings);
    }


    /**
     * @OA\Post(
     *     path="/api/customer/bookings",
     *     summary="Create a new booking",
     *     tags={"Customer Booking"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customer_name","showtime_id","seat_ids"},
     *             @OA\Property(property="customer_name", type="string"),
     *             @OA\Property(property="customer_phone", type="string"),
     *             @OA\Property(property="showtime_id", type="integer"),
     *             @OA\Property(property="seat_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking successful"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
      public function store(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'showtime_id' => 'required|exists:showtimes,id',
            'seat_ids' => 'required|array|min:1|max:10',
            'seat_ids.*' => 'required|exists:seats,id',
            'total_price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:khqr,aba',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if seats are available (not already booked)
            $bookedSeats = BookingSeat::whereIn('seat_id', $request->seat_ids)
                ->whereHas('booking', function ($query) use ($request) {
                    $query->where('showtime_id', $request->showtime_id)
                          ->where('status', '!=', 'cancelled');
                })
                ->pluck('seat_id')
                ->toArray();

            if (!empty($bookedSeats)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Some seats are already booked',
                    'booked_seats' => $bookedSeats
                ], 400);
            }

            // Create booking
            $booking = Booking::create([
                'user_id' => auth()->id(),
                'showtime_id' => $request->showtime_id,
                'total_price' => $request->total_price,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'booking_time' => now(),
            ]);

            // Create booking seats
            foreach ($request->seat_ids as $seatId) {
                BookingSeat::create([
                    'booking_id' => $booking->id,
                    'seat_id' => $seatId,
                ]);
            }

            DB::commit();

            // Load relationships
            $booking->load([
                'showtime.movie',
                'showtime.auditorium.cinema',
                'bookingSeats.seat'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => $booking
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Booking failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function show($id, Request $request)
    {
         try {
            $booking = Booking::with([
                'showtime.movie',
                'showtime.auditorium.cinema',
                'bookingSeats.seat',
                'user'
            ])->findOrFail($id);

            // Check if user owns this booking
            if ($booking->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $booking
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }
    }

    public function cancel($id, Request $request)
    {
        try {
            $booking = Booking::findOrFail($id);

            // Check if user owns this booking
            if ($booking->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Check if booking can be cancelled
            if ($booking->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel paid booking'
                ], 400);
            }

            $booking->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'data' => $booking
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use Illuminate\Http\Request;
use App\Models\{Booking, BookingSeat, Customer, Seat, Showtime};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $user = $request->user(); // Authenticated customer

        $data = $request->validate([
            'showtime_id' => 'required|exists:showtimes,id',
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'exists:seats,id',
        ]);

        return DB::transaction(function () use ($data, $user) {

            $showtime = Showtime::findOrFail($data['showtime_id']);

            // 1️⃣ Check for locked or already-booked seats
            $lockedSeats = DB::table('seat_locks')
                ->whereIn('seat_id', $data['seat_ids'])
                ->where('showtime_id', $showtime->id)
                ->where('locked_until', '>', now())
                ->pluck('seat_id')
                ->toArray();

            if (!empty($lockedSeats)) {
                return response()->json([
                    'message' => 'Some seats are currently locked. Please try again later.',
                    'locked_seats' => $lockedSeats,
                ], 409);
            }

            $alreadyBooked = BookingSeat::whereIn('seat_id', $data['seat_ids'])
                ->whereHas('booking', function ($q) use ($showtime) {
                    $q->where('showtime_id', $showtime->id);
                })
                ->exists();

            if ($alreadyBooked) {
                return response()->json([
                    'message' => 'One or more selected seats are already booked.',
                ], 409);
            }

            // 2️⃣ Lock selected seats (2 minutes)
            foreach ($data['seat_ids'] as $seatId) {
                DB::table('seat_locks')->insert([
                    'seat_id' => $seatId,
                    'showtime_id' => $showtime->id,
                    'locked_by' => $user->id,
                    'locked_until' => Carbon::now()->addMinutes(2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 3️⃣ Create booking
            $totalPrice = $showtime->price * count($data['seat_ids']);
            $booking = Booking::create([
                'customer_id' => $user->id,
                'showtime_id' => $showtime->id,
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            // 4️⃣ Attach seats using Eloquent pivot
            $booking->seats()->attach($data['seat_ids']);

            // 5️⃣ Remove locks after successful booking
            DB::table('seat_locks')
                ->where('locked_by', $user->id)
                ->whereIn('seat_id', $data['seat_ids'])
                ->delete();

            return new BookingResource($booking);
        });
    }
    public function show($id, Request $request)
    {
        $booking = Booking::with(['showtime.movie', 'seats'])
            ->where('customer_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($booking);
    }

    public function cancel($id, Request $request)
    {
        $booking = Booking::where('customer_id', $request->user()->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $booking->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Booking cancelled']);
    }
}

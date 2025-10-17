<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Services\KHQRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Validator};
use App\Http\Controllers\Controller;

class BookingController extends Controller
{
    protected $khqrService;

    public function __construct(KHQRService $khqrService)
    {
        $this->khqrService = $khqrService;
    }

    /**
     * Create a new booking
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

            // Check if seats are available
            $bookedSeats = BookingSeat::whereIn('seat_id', $request->seat_ids)
                ->whereHas('booking', function ($query) use ($request) {
                    $query->where('showtime_id', $request->showtime_id)
                          ->whereIn('status', ['pending', 'paid']);
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
            Log::error('Booking creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Booking failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate KHQR code for booking
     */
    public function generateKHQR($id)
    {
        try {
            $booking = Booking::with(['showtime.movie', 'bookingSeats.seat'])
                ->findOrFail($id);

            // Verify ownership
            if ($booking->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Check if booking is pending
            if ($booking->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking is not pending payment. Current status: ' . $booking->status
                ], 400);
            }

            // Generate KHQR code with booking ID as reference
            $qrData = $this->khqrService->generateQRCode(
                (string) $booking->id, // Use booking ID as reference
                $booking->total_price,
                'USD'
            );

            if (!$qrData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $qrData['message'] ?? 'Failed to generate QR code'
                ], 500);
            }

            // Store MD5 hash for payment verification
            $booking->update([
                'payment_reference' => $qrData['md5_hash'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => $qrData['qr_code'],
                    'qr_string' => $qrData['qr_string'] ?? null,
                    'reference_number' => $qrData['reference_number'],
                    'amount' => $qrData['amount'],
                    'currency' => $qrData['currency'],
                    'expires_at' => $qrData['expires_at'],
                    'md5_hash' => $qrData['md5_hash'] ?? null,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('KHQR generation failed: ' . $e->getMessage(), [
                'booking_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus($id)
    {
        try {
            $booking = Booking::findOrFail($id);

            // Verify ownership
            if ($booking->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // If already paid, return success
            if ($booking->status === 'paid') {
                return response()->json([
                    'success' => true,
                    'status' => 'paid',
                    'paid_at' => $booking->paid_at,
                ]);
            }

            // If no MD5 hash stored, cannot check
            if (!$booking->payment_reference) {
                return response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'No payment reference found'
                ], 400);
            }

            // Check payment status via KHQR service
            $result = $this->khqrService->checkTransactionByMd5($booking->payment_reference);

            if ($result['success'] && $result['status'] === 'success') {
                // Update booking status
                DB::beginTransaction();
                try {
                    $booking->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'status' => 'paid',
                        'message' => 'Payment confirmed',
                        'transaction_id' => $result['transaction_id'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            return response()->json([
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment not confirmed yet'
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status'
            ], 500);
        }
    }

    /**
     * KHQR Payment Webhook
     */
    public function khqrWebhook(Request $request)
    {
        try {
            // Log webhook data
            Log::info('KHQR Webhook received', $request->all());

            // Validate webhook signature if needed
            // TODO: Add webhook signature verification

            // Process webhook data
            $result = $this->khqrService->processWebhook($request->all());

            if (!$result['success']) {
                Log::warning('Webhook processing failed', [
                    'result' => $result,
                    'request' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Payment verification failed'
                ], 400);
            }

            // Get booking ID from bill_number or reference
            $bookingId = $result['bill_number'] ?? $request->input('billNumber');

            if (!$bookingId) {
                Log::error('No booking ID in webhook', $request->all());
                return response()->json([
                    'success' => false,
                    'message' => 'Missing booking reference'
                ], 400);
            }

            $booking = Booking::find($bookingId);

            if (!$booking) {
                Log::warning('Booking not found for webhook', ['booking_id' => $bookingId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            // Verify amount matches
            $amount = $result['amount'] ?? $request->input('amount');
            if ($amount && abs($booking->total_price - $amount) > 0.01) {
                Log::warning('Payment amount mismatch', [
                    'booking_id' => $bookingId,
                    'expected' => $booking->total_price,
                    'received' => $amount
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Amount mismatch'
                ], 400);
            }

            // Check if already paid
            if ($booking->status === 'paid') {
                Log::info('Booking already paid', ['booking_id' => $bookingId]);
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already confirmed'
                ]);
            }

            // Update booking status
            DB::beginTransaction();
            try {
                $booking->update([
                    'status' => 'paid',
                    'payment_reference' => $result['transaction_id'] ?? $booking->payment_reference,
                    'paid_at' => now(),
                ]);
                DB::commit();

                Log::info('Payment confirmed for booking', [
                    'booking_id' => $bookingId,
                    'transaction_id' => $result['transaction_id']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment confirmed'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('KHQR webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Get booking details
     */
    public function show($id)
    {
        try {
            $booking = Booking::with([
                'showtime.movie',
                'showtime.auditorium.cinema',
                'bookingSeats.seat',
                'user'
            ])->findOrFail($id);

            // Check if user owns this booking or is admin
            if ($booking->user_id !== auth()->id() && (!auth()->user() || auth()->user()->role !== 'admin')) {
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
            Log::error('Booking fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }
    }

    /**
     * Get user bookings
     */
    public function getUserBookings()
    {
        try {
            $bookings = Booking::with([
                'showtime.movie',
                'showtime.auditorium.cinema',
                'bookingSeats.seat'
            ])
            ->where('user_id', auth()->id())
            ->orderBy('booking_time', 'desc')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $bookings
            ]);

        } catch (\Exception $e) {
            Log::error('Fetch user bookings failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bookings'
            ], 500);
        }
    }

    /**
     * Cancel booking
     */
    public function cancel($id)
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
                    'message' => 'Cannot cancel paid booking. Please contact support for refunds.'
                ], 400);
            }

            if ($booking->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking is already cancelled'
                ], 400);
            }

            $booking->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'data' => $booking
            ]);

        } catch (\Exception $e) {
            Log::error('Booking cancellation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking'
            ], 500);
        }
    }
}

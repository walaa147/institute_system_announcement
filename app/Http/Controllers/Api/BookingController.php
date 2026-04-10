<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Http\Requests\Api\Secretary\StoreBookingRequest;
use App\Http\Requests\Api\Secretary\UpdateBookingStatusRequest;
use App\Http\Resources\Api\BookingResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function store(StoreBookingRequest $request)
    {
        try {
            $booking = $this->bookingService->createBooking($request->validated());

            return response()->json([
                'status' => true,
                'message' => __('validation.custom.booking.created_success'),
                'data' => new BookingResource($booking->load(['bookable', 'user']))
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking)
    {
        Gate::authorize('updateStatus', $booking);

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user->hasRole('secretary')) {
                $instituteId = $booking->bookable->institute_id ?? null;
                if ($instituteId !== $user->institute_id) {
                    return response()->json(['message' => __('validation.custom.booking.unauthorized_access')], 403);
                }
            }

            $updatedBooking = $this->bookingService->updateStatus($booking, $request->status, $user->id);

            return response()->json([
                'status' => true,
                'message' => __('validation.custom.booking.status_updated_success'),
                'data' => new BookingResource($updatedBooking)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        Gate::authorize('view', Booking::class);

        try {
            if ($user->hasRole('secretary')) {
                $bookings = Booking::with(['user', 'bookable'])
                    ->whereHasMorph('bookable', ['App\Models\Advertisement'], function ($query) use ($user) {
                        $query->where('institute_id', $user->institute_id);
                    })->latest()->paginate(10);
            } else {
                $bookings = $user->bookings()->with(['bookable'])->latest()->paginate(10);
            }

            return BookingResource::collection($bookings);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('validation.custom.booking.fetch_failed')], 500);
        }
    }

    public function simulatePayment(Booking $booking)
    {
        if ($booking->status === 'cancelled') {
            return response()->json(['message' => __('validation.custom.booking.payment_failed')], 422);
        }

        $mockBankData = [
            'id' => 'BANK_AUTH_' . strtoupper(uniqid()),
            'transaction_id' => 'TXN_' . time(),
            'method' => 'visa_card'
        ];

        $authorizedBooking = $this->bookingService->markAsAuthorized($booking, $mockBankData);

        return response()->json([
            'status'  => true,
            'message' => __('validation.custom.booking.payment_authorized'),
            'data'    => new BookingResource($authorizedBooking)
        ]);
    }
}

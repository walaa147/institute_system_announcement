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
            if($e->getMessage() === "FULL_MAX_SEATS") {
                return response()->json([
                'status' => false,
                'code'   => 'WAITLIST_OPTION', // كود خاص للموبايل ليظهر زر "انضم للانتظار"
                'message' => __('validation.custom.booking.no_seats_available'),
                'bookable_id' => $request->bookable_id,
                'bookable_type' => 'App\Models\Advertisement'
            ], 200); // نرجع 200 لأنها ليست خطأ في السيرفر بل حالة منطقية
        }

            return response()->json(['status' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking)
{
    // البوليسي الآن يقوم بكل العمل
    Gate::authorize('updateStatus', $booking);

    try {
        $user = Auth::user();
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

    // App\Http\Controllers\Api\BookingController.php

public function index()
{
    // نستخدم viewAny بدلاً من view
    Gate::authorize('viewAny', Booking::class);
/** @var \App\Models\User $user */
    $user = Auth::user();

    // بناء الاستعلام بناءً على الرتبة
    $query = Booking::with(['user', 'bookable']);

    if ($user->hasRole('secretary')) {
        $query->whereHasMorph('bookable', [\App\Models\Advertisement::class], function ($q) use ($user) {
            $q->where('institute_id', $user->institute_id);
        });
    } elseif ($user->hasRole('student')) {
        $query->where('user_id', $user->id);
    }
    $bookings = $query->latest()->paginate(10);
    if ($bookings->isEmpty()) {
        return response()->json([
            'status' => true,
            'message' => __('validation.custom.booking.list_empty'),

            'data' => []
        ], 200);
    }

    return BookingResource::collection($bookings)->additional([
        'status' => true,
        'message' => __('validation.custom.booking.list_retrieved_success')
    ]);
}
public function show($id)
{
    $booking = Booking::with(['user', 'bookable', 'processor'])->find($id);

    if (!$booking) {
        return response()->json([
            'status' => false,
            'message' => __('validation.custom.booking.not_found')
        ], 404);
    }
{
    // فحص الصلاحية: هل هذا الحجز يخص الطالب؟ أو هل هو ضمن معهد السكرتير؟
    // لارافيل سيبحث تلقائياً عن دالة view($user, $booking) في الـ BookingPolicy
    Gate::authorize('view', $booking);

    return response()->json([
        'status' => true,
        'data' => new BookingResource($booking->load(['user', 'bookable', 'processor']))
    ]);
}
}
public function cancel($id)
{
    $booking = Booking::find($id);

    if (!$booking) {
        return response()->json([
            'status' => false,
            'message' => '__("validation.custom.booking.not_found")'
        ], 404);
    }
    // فحص الصلاحية من البوليسي
    $response = Gate::inspect('cancel', $booking);
    if ($response->denied()) {
        return response()->json([
            'status' => false,
            'message' => $response->message() // هنا ستظهر الرسالة التي كتبناها في الـ Policy
        ], 403);
    }

    try {
        // استدعاء الخدمة لتنفيذ الإلغاء وتحرير المقعد
        $updatedBooking = $this->bookingService->cancelByStudent($booking);

        return response()->json([
            'status' => true,
            'message' => __('validation.custom.booking.cancelled_success'),
            'data' => new BookingResource($updatedBooking)
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => $e->getMessage()], 400);
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

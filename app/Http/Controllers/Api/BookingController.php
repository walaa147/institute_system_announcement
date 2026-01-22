<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\CompleteBookingRequest;
use App\Models\Course;
use App\Services\BookingService;
use App\Http\Resources\BookingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $service)
    {
        // يتطلب تسجيل الدخول لجميع العمليات في هذا المتحكم

    }

    /**
     * تسجيل حجز مبدئي لكورس (للطالب).
     */
public function bookItem(StoreBookingRequest $request): JsonResponse
{
    try {
        // 1. تحديد العنصر (كورس أو دبلوم)
        if ($request->has('diploma_id')) {
            $item = \App\Models\Diploma::findOrFail($request->diploma_id);
        } else {
            $item = \App\Models\Course::findOrFail($request->course_id);
        }

        // 2. استدعاء الخدمة لإجراء الحجز أو جلبه إذا كان موجوداً
        $booking = $this->service->bookItem(Auth::id(), $item);

        // 3. إذا كان الحجز موجوداً مسبقاً (وليس جديداً)
        if (!$booking->wasRecentlyCreated) {
            return response()->json([
                'message' => 'عذراً، لقد قمت بحجز هذا ' . ($booking->diploma_id ? 'الدبلوم' : 'الكورس') . ' مسبقاً.',
                'status'  => 'info'
            ], 200); // إرسال رسالة فقط بدون data
        }

        // 4. إذا كان الحجز جديداً
        return response()->json([
            'message' => 'تم الحجز المبدئي بنجاح.',
            'status'  => 'success',
            'data'    => new BookingResource($booking->load(['user', 'course', 'diploma'])),
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'فشل الحجز: ' . $e->getMessage(),
            'status' => 'error'
        ], 500);
    }
}

    /**
     * عرض قائمة الحجوزات المعلقة (لوحة السكرتير/الإدارة).
     */
    public function pending(): AnonymousResourceCollection
    {
        $bookings = $this->service->getPendingBookings();
        return BookingResource::collection($bookings);
    }

    /**
     * إنهاء عملية التسجيل (الدفع وتغيير الدور) - للسكرتير.
     */
    public function complete(CompleteBookingRequest $request): JsonResponse
    {
        try {
            $bookingId = $request->validated('booking_id');
            $paymentData = $request->only(['payment_method', 'payment_notes']);

            $completedBooking = $this->service->completeBooking($bookingId, $paymentData);

            return response()->json([
                'message' => 'تم إنهاء التسجيل بنجاح! تم تحديث حالة الدفع وتعيين المستخدم كطالب.',
                'status' => 'success',
                'data' => new BookingResource($completedBooking),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشلت عملية إنهاء التسجيل: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
}

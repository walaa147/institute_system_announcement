<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Http\Requests\Api\Booking\StoreBookingRequest;
use App\Http\Requests\Api\Booking\UpdateBookingStatusRequest;
use App\Http\Resources\Api\Booking\BookingResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    /**
     * إنشاء حجز جديد (الطالب)
     */
    public function store(StoreBookingRequest $request)
    {
        try {
            // السيرفس الآن يعيد BookingResource مباشرة
            $resource = $this->bookingService->createBooking($request->validated());

            return response()->json([
                'status' => true,
                'message' => __('validation.custom.booking.created_success'),
                'data' => $resource
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * تحديث الحالة (السكرتير) - يتضمن Capture للمبلغ وإنقاص المقاعد
     */
    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking)
    {
        Gate::authorize('updateStatus', $booking);

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // التحقق من صلاحية المعهد للسكرتير
            if ($user->hasRole('secretary')) {
                // نصل للمعهد عبر العلاقة المتعددة (الإعلان)
                $instituteId = $booking->bookable->institute_id ?? null;
                if ($instituteId !== $user->institute_id) {
                    return response()->json(['message' => __('validation.custom.booking.unauthorized_access')], 403);
                }
            }

            // السيرفس يعيد BookingResource
            $resource = $this->bookingService->updateStatus($booking, $request->status, $user->id);

            return response()->json([
                'status' => true,
                'message' => __('validation.custom.booking.status_updated_success'),
                'data' => $resource
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * عرض الحجوزات (للسكرتير والطالب)
     */
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

    /**
     * محاكاة الدفع البنكي (Authorization)
     * هنا الطالب يدفع من التطبيق، والبنك يحجز المبلغ
     */
    public function simulatePayment(Booking $booking)
    {
        if ($booking->status === 'cancelled') {
            return response()->json(['message' => __('validation.custom.booking.payment_failed')], 422);
        }

        // بيانات وهمية تحاكي استجابة البنك
        $mockBankData = [
            'id' => 'BANK_AUTH_' . strtoupper(uniqid()),
            'transaction_id' => 'TXN_' . time(),
            'method' => 'visa_card'
        ];

        // السيرفس الآن يعيد BookingResource بعد تحويل الحالة لـ authorized
        $resource = $this->bookingService->markAsAuthorized($booking, $mockBankData);

        return response()->json([
            'status'  => true,
            'message' => __('validation.custom.booking.payment_authorized'),
            'data'    => $resource
        ]);
    }
}

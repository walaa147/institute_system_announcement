<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Diploma;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class BookingService
{
    /**
     * 1. دالة الحجز المبدئي (للكورس أو الدبلوم)
     */
    public function bookItem(int $userId, $item): Booking
    {
        $isDiploma = $item instanceof Diploma;
        $courseId = $isDiploma ? null : $item->id;
        $diplomaId = $isDiploma ? $item->id : null;

        // منع التكرار
        $existingBooking = Booking::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('diploma_id', $diplomaId)
            ->first();

        if ($existingBooking) {
            return $existingBooking;
        }

        // تحديد السعر (price للكورس و total_cost للدبلوم)
        $price = $isDiploma ? (float)($item->total_cost ?? 0) : (float)($item->price ?? 0);

        return DB::transaction(function () use ($userId, $courseId, $diplomaId, $price) {
            return Booking::create([
                'user_id'         => $userId,
                'course_id'       => $courseId,
                'diploma_id'      => $diplomaId,
                'original_price'  => $price,
                'final_price'     => $price,
                'discount_amount' => 0,
                'is_paid'         => false,
                'booking_date'    => now(),
            ]);
        });
    }

    /**
     * 2. دالة عرض الحجوزات المعلقة (للسكرتير)
     */
    public function getPendingBookings(): Collection
    {
        return Booking::with(['user', 'course', 'diploma'])
            ->where('is_paid', false)
            ->latest()
            ->get();
    }

    /**
     * 3. دالة إنهاء الحجز (تفعيل الدفع وتحويل المستخدم لطالب)
     */
    public function completeBooking(int $bookingId, array $paymentData = []): Booking
    {
        // جلب الحجز مع كافة العلاقات للتأكد من البيانات
        $booking = Booking::with(['user', 'course', 'diploma'])->findOrFail($bookingId);

        return DB::transaction(function () use ($booking, $paymentData) {

            // أ. تحديث حالة الحجز
            $booking->update([
                'is_paid' => true,
                'payment_details' => array_merge(
                    $booking->payment_details ?? [],
                    [
                        'completed_by'    => Auth::user()?->name ?? 'System/Admin',
                        'completion_date' => now()->format('Y-m-d H:i:s'),
                        'method'          => $paymentData['payment_method'] ?? 'Cash/Office',
                        'notes'           => $paymentData['payment_notes'] ?? null,
                    ]
                ),
            ]);

            // ب. تحديث دور المستخدم (Spatie Permission)
            $user = $booking->user;

            // تحويله من عميل إلى طالب بمجرد الدفع لأول مرة
            if (!$user->hasRole('student')) {
                $user->assignRole('student');
            }

            // اختياري: سحب دور عميل إذا كنت لا تريد بقاء الدورين معاً
            if ($user->hasRole('customer')) {
                $user->removeRole('customer');
            }

            return $booking;
        });
    }

    /**
     * 4. دالة حساب الأسعار (إذا أردت إضافة خصومات مستقبلاً)
     */
    public function calculateFinalPrice($price, float $discountPercentage = 0.0): float
    {
        $discountAmount = $price * ($discountPercentage / 100);
        return max(0.0, $price - $discountAmount);
    }
}

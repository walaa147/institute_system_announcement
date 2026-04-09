<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Advertisement;
use App\Http\Resources\Api\Booking\BookingResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BookingService
{
    /**
     * نستخدم InstituteService لتحديث ترتيب المعهد بناءً على سرعة الاستجابة
     */
    public function __construct(protected InstituteService $instituteService) {}

    /**
     * 1. إنشاء الحجز (Student Side)
     * يتم حساب السعر آلياً بناءً على توفر مقاعد الحجز المبكر وتاريخ انتهاء الخصم.
     */
    public function createBooking(array $data): BookingResource
    {
        return DB::transaction(function () use ($data) {
            // جلب الإعلان والتأكد من وجوده
            $advertisement = Advertisement::findOrFail($data['bookable_id']);

            // التحقق من أن الإعلان متاح للحجز
            if (!$advertisement->is_open_for_booking) {
                throw new \Exception(__('validation.custom.booking.booking_closed'));
            }

            // منع الطالب من تكرار الحجز لنفس الإعلان
            $exists = Booking::where('user_id', Auth::id())
                ->where('bookable_id', $data['bookable_id'])
                ->where('bookable_type', $data['bookable_type'])
                ->whereIn('status', ['draft', 'confirmed', 'attended'])
                ->exists();

            if ($exists) {
                throw new \Exception(__('validation.custom.booking.already_exists'));
            }

            $now = now();

            // منطق الحجز المبكر: (التاريخ لم ينتهِ + المقاعد المبكرة لم تكتمل)
            $isEarlyBird = false;
            if ($advertisement->early_paid_price > 0) {
                $isWithinDate = $advertisement->discount_expiry ? $now->lte($advertisement->discount_expiry) : true;
                $hasEarlySeats = $advertisement->current_seats_taken < ($advertisement->early_paid_seats_limit ?? 999);

                if ($isWithinDate && $hasEarlySeats) {
                    $isEarlyBird = true;
                }
            }

            // تحديد الأسعار
            $originalPrice = $advertisement->price_before_discount ?? 0;
            $finalPrice = $isEarlyBird
                ? $advertisement->early_paid_price
                : ($advertisement->price_after_discount ?? $originalPrice);

            $booking = Booking::create([
                'user_id'         => Auth::id(),
                'bookable_id'     => $data['bookable_id'],
                'bookable_type'   => $data['bookable_type'],
                'original_price'  => $originalPrice,
                'discount_amount' => $originalPrice - $finalPrice,
                'final_price'     => $finalPrice,
                'status'          => 'draft',
                'payment_status'  => 'pending',
                'booking_type'    => $isEarlyBird ? 'early' : 'regular',
                'booking_date'    => $now,
            ]);

            return new BookingResource($booking->load(['bookable', 'user']));
        });
    }

    /**
     * 2. تحديث الحالة (Secretary Side)
     * عند التأكيد: يتم سحب المبلغ فعلياً (Capture) وزيادة عدد المقاعد المأخوذة.
     */
    public function updateStatus(Booking $booking, string $status, int $adminId): BookingResource
    {
        // منع تعديل الحجوزات الملغاة
        if ($booking->status === 'cancelled') {
            throw new \Exception(__('validation.custom.booking.not_allowed_status'));
        }

        return DB::transaction(function () use ($booking, $status, $adminId) {

            if ($status === 'confirmed' && $booking->status !== 'confirmed') {
                $advertisement = $booking->bookable;

                // التحقق من توفر المقاعد الكلية
                if ($advertisement->max_seats && $advertisement->current_seats_taken >= $advertisement->max_seats) {
                    throw new \Exception(__('validation.custom.booking.no_seats_available'));
                }

                // تحديث المقاعد في جدول الإعلانات
                $advertisement->increment('current_seats_taken');

                // تحديث بيانات الحجز
                $booking->update([
                    'status'         => 'confirmed',
                    'confirmed_at'   => now(),
                    'processed_by'   => $adminId,
                    'is_paid'        => true,
                    'payment_status' => 'paid' // تحويل الحالة من authorized إلى paid
                ]);

                // تحديث مؤشرات أداء المعهد (سرعة الرد)
                $this->updateInstitutePerformance($booking);
            } else {
                // في حالة الإلغاء أو "تم الحضور"
                $booking->update(['status' => $status]);
            }

            return new BookingResource($booking->refresh()->load(['bookable', 'user', 'processor']));
        });
    }

    /**
     * 3. محاكاة تفويض الدفع (Bank Authorization)
     * الطالب يدفع من التطبيق -> البنك يحجز المبلغ -> نغير الحالة لـ authorized.
     */
    public function markAsAuthorized(Booking $booking, array $bankData): BookingResource
    {
        return DB::transaction(function () use ($booking, $bankData) {
            $booking->update([
                'payment_status'  => 'authorized',
                'bank_payment_id' => $bankData['id'] ?? 'BANK-' . uniqid(),
                'transaction_id'  => $bankData['transaction_id'] ?? 'TRX-' . time(),
                'payment_method'  => $bankData['method'] ?? 'card',
                'payment_payload' => $bankData,
            ]);

            return new BookingResource($booking->refresh()->load(['bookable', 'user']));
        });
    }

    /**
     * دالة داخلية لحساب متوسط سرعة رد المعهد وتحديث ترتيبه
     */
    protected function updateInstitutePerformance(Booking $booking)
    {
        // الوصول للمعهد من خلال الإعلان (bookable)
        $institute = $booking->bookable->institute ?? null;

        if ($institute && $booking->confirmed_at) {
            // حساب الفرق الزمني بالدقائق
            $diff = $booking->created_at->diffInMinutes($booking->confirmed_at);

            // حساب المتوسط المتحرك
            $currentAvg = $institute->avg_response_time ?? 0;
            $newAvg = ($currentAvg == 0) ? $diff : ($currentAvg + $diff) / 2;

            $institute->update(['avg_response_time' => $newAvg]);

            // تحديث الـ priority بناءً على السرعة الجديدة
            $this->instituteService->refreshPriority($institute);
        }
    }
}

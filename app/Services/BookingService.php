<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Advertisement;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

class BookingService
{
    public function __construct(protected InstituteService $instituteService) {}

    /**
     * 1. إنشاء الحجز
     */
public function createBooking(array $data): Booking
{
    // هذا السطر سيكشف لنا الحقيقة
    // dd(\DB::table('advertisements')->where('id', $data['bookable_id'])->first());


    return DB::transaction(function () use ($data) {

        // 1. استخدم التحقق اليدوي بدلاً من findOrFail للتأكد من الخطأ
        $advertisement = Advertisement::find($data['bookable_id']);

        if (!$advertisement) {
            throw new \Exception("الإعلان رقم ({$data['bookable_id']}) غير موجود في النظام.");
        }

        if (!$advertisement->is_open_for_booking) {
            throw new \Exception(__('validation.custom.booking.booking_closed'));
        }

        // 2. تأكد من استخدام نفس الـ Type المثبت في الـ Request
        $exists = Booking::where('user_id', Auth::id())
            ->where('bookable_id', $data['bookable_id'])
            ->where('bookable_type', 'App\Models\Advertisement') // ثبت المسار هنا أيضاً
            ->whereIn('status', ['draft', 'confirmed', 'attended'])
            ->exists();

        if ($exists) {
            throw new \Exception(__('validation.custom.booking.already_exists'));
        }


            $now = now();
            $isEarlyBird = false;
            if ($advertisement->early_paid_price > 0) {
                $isWithinDate = $advertisement->discount_expiry ? $now->lte($advertisement->discount_expiry) : true;
                $hasEarlySeats = $advertisement->current_seats_taken < ($advertisement->early_paid_seats_limit ?? 999);
                if ($isWithinDate && $hasEarlySeats) {
                    $isEarlyBird = true;
                }
            }

            $originalPrice = $advertisement->price_before_discount ?? 0;
            $finalPrice = $isEarlyBird
                ? $advertisement->early_paid_price
                : ($advertisement->price_after_discount ?? $originalPrice);


        return Booking::create([
            'user_id'         => Auth::id(),
            'bookable_id'     => $data['bookable_id'],
            'bookable_type'   => 'App\Models\Advertisement', // تأكد من الثبات هنا
            'original_price'  => $originalPrice,
            'discount_amount' => $originalPrice - $finalPrice,
            'final_price'     => $finalPrice,
            'status'          => 'draft',
            'payment_status'  => 'pending',
            'booking_type'    => $isEarlyBird ? 'early' : 'regular',
            'booking_date'    => now(),
        ]);
    });
}

    /**
     * 2. تحديث الحالة
     */
    public function updateStatus(Booking $booking, string $status, int $adminId): Booking
    {
        if ($booking->status === 'cancelled') {
            throw new \Exception(__('validation.custom.booking.not_allowed_status'));
        }

        return DB::transaction(function () use ($booking, $status, $adminId) {
            if ($status === 'confirmed' && $booking->status !== 'confirmed') {
                $advertisement = $booking->bookable;

                if ($advertisement->max_seats && $advertisement->current_seats_taken >= $advertisement->max_seats) {
                    throw new \Exception(__('validation.custom.booking.no_seats_available'));
                }

                $advertisement->increment('current_seats_taken');

                $booking->update([
                    'status'         => 'confirmed',
                    'confirmed_at'   => now(),
                    'processed_by'   => $adminId,
                    'is_paid'        => true,
                    'payment_status' => 'paid'
                ]);

                $this->updateInstitutePerformance($booking);
            } else {
                $booking->update(['status' => $status]);
            }

            return $booking->refresh()->load(['bookable', 'user', 'processor']);
        });
    }

    /**
     * 3. تفويض الدفع
     */
    public function markAsAuthorized(Booking $booking, array $bankData): Booking
    {
        return DB::transaction(function () use ($booking, $bankData) {
            $booking->update([
                'payment_status'  => 'authorized',
                'bank_payment_id' => $bankData['id'] ?? 'BANK-' . uniqid(),
                'transaction_id'  => $bankData['transaction_id'] ?? 'TRX-' . time(),
                'payment_method'  => $bankData['method'] ?? 'card',
                'payment_payload' => $bankData,
            ]);

            return $booking->refresh()->load(['bookable', 'user']);
        });
    }

    protected function updateInstitutePerformance(Booking $booking)
    {
        $institute = $booking->bookable->institute ?? null;
        if ($institute && $booking->confirmed_at) {
            $diff = $booking->created_at->diffInMinutes($booking->confirmed_at);
            $currentAvg = $institute->avg_response_time ?? 0;
            $newAvg = ($currentAvg == 0) ? $diff : ($currentAvg + $diff) / 2;
            $institute->update(['avg_response_time' => $newAvg]);
            $this->instituteService->refreshPriority($institute);
        }
    }
}

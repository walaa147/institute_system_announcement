<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Advertisement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BookingService
{
    public function __construct(protected InstituteService $instituteService) {}

    /**
     * 1. إنشاء الحجز
     */
public function createBooking(array $data): Booking
{
    return DB::transaction(function () use ($data) {

        // 1. التحقق من وجود الإعلان وصلاحيته للحجز
       $advertisement = Advertisement::where('id', $data['bookable_id'])
            ->lockForUpdate()
            ->first();
        if (!$advertisement) {
            throw new \Exception("الإعلان رقم ({$data['bookable_id']}) غير موجود في النظام.");
        }
        if ($advertisement->max_seats && $advertisement->current_seats_taken >= $advertisement->max_seats) {
            throw new \Exception("FULL_MAX_SEATS");
        }

        if (!$advertisement->is_open_for_booking) {
            throw new \Exception(__('validation.custom.booking.booking_closed'));
        }

        // 2. البحث عن حجز سابق (حتى لو كان ملغياً) لتجنب قيد Unique في قاعدة البيانات
        $existingBooking = Booking::where('user_id', Auth::id())
            ->where('bookable_id', $data['bookable_id'])
            ->where('bookable_type', 'App\Models\Advertisement')
            ->first();

        if ($existingBooking) {
            // إذا كان الحجز نشطاً (مسودة أو مؤكد)، نرفض التكرار
            if (in_array($existingBooking->status, ['draft', 'confirmed', 'attended'])) {
                throw new \Exception(__('validation.custom.booking.already_exists'));
            }

            // إذا وصلنا هنا، يعني أن الحجز القديم حالته 'cancelled'
            // سنقوم بتحديثه (إعادة إحيائه) بدلاً من إنشاء سجل جديد
            return $this->updateExistingBooking($existingBooking, $advertisement);
        }

        // 3. إذا لم يوجد حجز سابق نهائياً، ننشئ واحداً جديداً
        $pricing = $this->calculatePricing($advertisement);

        return Booking::create([
            'user_id'         => Auth::id(),
            'bookable_id'     => $data['bookable_id'],
            'bookable_type'   => 'App\Models\Advertisement',
            'original_price'  => $pricing['original_price'],
            'discount_amount' => $pricing['discount_amount'],
            'final_price'     => $pricing['final_price'],
            'status'          => 'draft',
            'payment_status'  => 'pending',
            'booking_type'    => $pricing['booking_type'],
            'booking_date'    => now(),
        ]);
    });
}

/**
 * دالة مساعدة لحساب السعر (لتجنب تكرار الكود)
 */
private function calculatePricing($advertisement): array
{
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

    return [
        'original_price'  => $originalPrice,
        'final_price'     => $finalPrice,
        'discount_amount' => $originalPrice - $finalPrice,
        'booking_type'    => $isEarlyBird ? 'early' : 'regular'
    ];
}

/**
 * دالة مساعدة لتحديث الحجز الملغي
 */
private function updateExistingBooking(Booking $booking, $advertisement): Booking
{
    $pricing = $this->calculatePricing($advertisement);

    $booking->update([
        'status'          => 'draft',
        'payment_status'  => 'pending',
        'original_price'  => $pricing['original_price'],
        'discount_amount' => $pricing['discount_amount'],
        'final_price'     => $pricing['final_price'],
        'booking_type'    => $pricing['booking_type'],
        'booking_date'    => now(), // إعادة ضبط تاريخ الحجز ليبدأ من جديد
        'admin_notes'     => 'تم إعادة تفعيل الحجز بعد الإلغاء.'
    ]);

    return $booking->refresh();
}

    /**
     * 2. تحديث الحالة
     */
// App\Services\BookingService.php

public function updateStatus(Booking $booking, string $status, int $adminId): Booking
{
    return DB::transaction(function () use ($booking, $status, $adminId) {

        // أهم خطوة: قفل سجل الإعلان للتأكد من عدد المقاعد بدقة
        $advertisement = $booking->bookable()->lockForUpdate()->first();

        if ($status === 'confirmed' && $booking->status !== 'confirmed') {
            if ($advertisement->max_seats && $advertisement->current_seats_taken >= $advertisement->max_seats) {
                throw new \Exception(__('validation.custom.booking.no_seats_available'));
            }

            $advertisement->increment('current_seats_taken');

            $booking->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'processed_by' => $adminId,
                'is_paid' => true,
                'payment_status' => 'paid'
            ]);
            $this->updateInstitutePerformance($booking);
        }

        // حالة الإلغاء: إذا كان مؤكداً، نحرر المقعد
        elseif ($status === 'cancelled' && $booking->status === 'confirmed') {
            $advertisement->decrement('current_seats_taken');
            $this->processNextInWaitlist($advertisement);
            $booking->update(['status' => 'cancelled', 'payment_status' => 'refunded']);
        }
        else {
            $booking->update(['status' => $status]);
        }

        return $booking->refresh();
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
    /**
 * إلغاء الحجز من قبل الطالب
 */
public function cancelByStudent(Booking $booking): Booking
{
    // البدء في عملية مالية آمنة
    return DB::transaction(function () use ($booking) {

        // 1. قفل سجل الإعلان (لضمان تحديث المقاعد بدقة)
        $advertisement = $booking->bookable()->lockForUpdate()->first();

        // 2. التحقق: هل الحجز في حالة تسمح بالإلغاء؟
        // (لا نؤيد إلغاء الحجز إذا كان 'attended' أو 'cancelled' مسبقاً)
        if (in_array($booking->status, ['cancelled', 'attended'])) {
            throw new \Exception(__('validation.custom.booking.cannot_cancel_now'));
        }

        // 3. إذا كان الحجز مؤكداً (Confirmed)، يجب إعادة المقعد للإعلان
        if ($booking->status === 'confirmed') {
            $advertisement->decrement('current_seats_taken');
        }


        // 4. تحديث بيانات الحجز
        $booking->update([
            'status' => 'cancelled',
            'admin_notes' => 'تم الإلغاء بواسطة الطالب في: ' . now()->format('Y-m-d H:i'),
            // إذا كان هناك نظام مالي معقد، يمكن تغيير حالة الدفع إلى 'refund_pending' هنا
        ]);
        $this->processNextInWaitlist($advertisement);

        return $booking->refresh();
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
 public function processNextInWaitlist($advertisement)
{
    // 1. التأكد أن الأوبجكت ليس نال
    if (!$advertisement || !isset($advertisement->id)) {
        Log::error("Waitlist Error: Advertisement object is null or has no ID");
        return;
    }

    // 2. تنظيف اسم الكلاس من أي علامات مائلة إضافية لضمان مطابقة الداتابيز
    $className = trim($advertisement->getMorphClass(), '\\');

    // 3. البحث باستخدام استعلام صريح ونظيف
    $next = \App\Models\WaitingList::where('bookable_id', $advertisement->id)
        ->where('bookable_type', $className)
        ->where('status', 'waiting')
        ->orderBy('priority_order', 'asc')
        ->first();

    if ($next) {
        // تحديث الحالة مباشرة في قاعدة البيانات
        $updated = DB::table('waiting_lists')
            ->where('id', $next->id)
            ->update(['status' => 'notified']);

        if ($updated) {
            Log::info("Success: Status changed to notified for User: {$next->user_id}");
        }
    } else {
        // هذا اللوج سيخبرك إذا كان المشكل في الـ ID أو النوع
        Log::warning("No waiting record found for Ad ID: {$advertisement->id} and Type: {$className}");
    }
}
}

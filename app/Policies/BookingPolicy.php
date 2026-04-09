<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * من يمكنه رؤية تفاصيل حجز معين؟
     */
    public function view(User $user, Booking $booking): bool
    {
        // الطالب يرى حجزه فقط
        if ($user->hasRole('student')) {
            return $user->id === $booking->user_id;
        }

        // السكرتير يرى حجز معهده فقط
        if ($user->hasRole('secretary')) {
            return $user->institute_id === $booking->institute_id;
        }

        // الأدمن يرى كل شيء
        return $user->hasRole('admin');
    }

    /**
     * من يمكنه تحديث حالة الحجز (التأكيد/الإلغاء)؟
     */
    public function updateStatus(User $user, Booking $booking): bool
    {
        // السكرتير يحدث حجز معهده فقط وبشرط ألا يكون الحجز ملغى نهائياً
        if ($user->hasRole('secretary')) {
            return $user->institute_id === $booking->institute_id
                   && $booking->status !== 'cancelled';
        }

        return $user->hasRole('admin');
    }
}

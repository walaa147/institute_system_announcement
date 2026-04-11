<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($user->hasRole('student')) {
            return $user->id === $booking->user_id;
        }

        if ($user->hasRole('secretary')) {
            // الوصول للمعهد من خلال الإعلان (bookable)
            return $user->institute_id === ($booking->bookable->institute_id ?? null);
        }

        return $user->hasRole(['admin', 'super_admin']);
    }

    public function updateStatus(User $user, Booking $booking): bool
    {
        if ($user->hasRole('secretary')) {
            // التعديل هنا: نتحقق من المعهد عبر علاقة bookable
            return $user->institute_id === ($booking->bookable->institute_id ?? null)
                   && $booking->status !== 'cancelled';
        }

        return $user->hasRole(['admin', 'super_admin']);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        // الطالب يلغي حجز نفسه فقط إذا لم يتم تأكيده بعد
        if ($user->hasRole('student')) {
            return $user->id === $booking->user_id && $booking->status === 'draft';
        }

        return $user->hasRole(['admin', 'super_admin']);
    }
}

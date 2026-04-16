<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): Response
{
    // 1. الأولوية الأولى: هل هذا الحجز ملك للمستخدم (طالب أو سكرتير حجز لنفسه)؟
    if ($user->id === $booking->user_id) {
        return Response::allow();
    }

    // 2. الأولوية الثانية: إذا لم يكن صاحب الحجز، هل هو سكرتير المعهد؟
    if ($user->hasRole('secretary')) {
        return $user->institute_id === ($booking->bookable->institute_id ?? null)
            ? Response::allow()
            : Response::deny(__('validation.custom.booking.institute_mismatch'));
    }

    // 3. السوبر أدمن
    return $user->hasRole(['admin', 'super_admin'])
        ? Response::allow()
        : Response::deny(__('validation.custom.booking.access_denied'));
}

public function cancel(User $user, Booking $booking): Response
{
    // 1. الأولوية الأولى: معاملة المستخدم كطالب (سواء كان دوره سكرتير أو طالب)
    // إذا كان هو صاحب الحجز، نطبق عليه شروط إلغاء الطلاب
    if ($user->id === $booking->user_id) {
        if (!in_array($booking->status, ['draft', 'confirmed'])) {
            return Response::deny(__('validation.custom.booking.cannot_cancel_current_status'));
        }
        return Response::allow();
    }

    // 2. الأولوية الثانية: هل هو سكرتير المعهد الذي يتبع له الإعلان؟
    // (هنا السكرتير يلغي حجز طالب آخر في معهده)
    if ($user->hasRole('secretary')) {
        return $user->institute_id === ($booking->bookable->institute_id ?? null)
            ? Response::allow()
            : Response::deny(__('validation.custom.booking.institute_mismatch'));
    }

    return $user->hasRole(['admin', 'super_admin'])
        ? Response::allow()
        : Response::deny(__('validation.custom.booking.access_denied'));
}
}

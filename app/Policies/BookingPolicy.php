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

        // 3. السوبر أدمن والأدمن
        return $user->hasRole(['admin', 'super_admin'])
            ? Response::allow()
            : Response::deny(__('validation.custom.booking.access_denied'));
    }

    public function updateStatus(User $user, Booking $booking): Response
    {
        // سكرتير المعهد التابع له الإعلان
        if ($user->hasRole('secretary')) {
            return $user->institute_id === ($booking->bookable->institute_id ?? null)
                ? Response::allow()
                : Response::deny(__('validation.custom.booking.institute_mismatch'));
        }

        // السوبر أدمن والأدمن
        return $user->hasRole(['admin', 'super_admin'])
            ? Response::allow()
            : Response::deny(__('validation.custom.booking.access_denied'));
    }

    public function cancel(User $user, Booking $booking): Response
    {
        // 1. الأولوية الأولى: إذا كان هو صاحب الحجز
        if ($user->id === $booking->user_id) {
            if (!in_array($booking->status, ['draft', 'confirmed'])) {
                return Response::deny(__('validation.custom.booking.cannot_cancel_current_status'));
            }
            return Response::allow();
        }

        // 2. الأولوية الثانية: سكرتير المعهد
        if ($user->hasRole('secretary')) {
            return $user->institute_id === ($booking->bookable->institute_id ?? null)
                ? Response::allow()
                : Response::deny(__('validation.custom.booking.institute_mismatch'));
        }

        // 3. السوبر أدمن والأدمن
        return $user->hasRole(['admin', 'super_admin'])
            ? Response::allow()
            : Response::deny(__('validation.custom.booking.access_denied'));
    }
}
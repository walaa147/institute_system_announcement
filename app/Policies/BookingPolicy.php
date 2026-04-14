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
        if ($user->hasRole('student')) {
            return $user->id === $booking->user_id
                ? Response::allow()
                : Response::deny(__('validation.custom.booking.not_your_booking'));
        }

        if ($user->hasRole('secretary')) {
            return $user->institute_id === ($booking->bookable->institute_id ?? null)
                ? Response::allow()
                : Response::deny(__('validation.custom.booking.institute_mismatch'));
        }

        return $user->hasRole(['admin', 'super_admin'])
            ? Response::allow()
            : Response::deny(__('validation.custom.booking.access_denied'));
    }

    public function updateStatus(User $user, Booking $booking): Response
    {
        if ($user->hasRole('secretary')) {
            if ($user->institute_id !== ($booking->bookable->institute_id ?? null)) {
                return Response::deny(__('validation.custom.booking.institute_mismatch'));
            }

            if ($booking->status === 'cancelled') {
                return Response::deny(__('validation.custom.booking.cannot_update_cancelled'));
            }

            return Response::allow();
        }

        return $user->hasRole(['admin', 'super_admin'])
            ? Response::allow()
            : Response::deny(__('validation.custom.booking.access_denied'));
    }


    public function cancel(User $user, Booking $booking): Response
    {
        if ($user->hasRole('student')) {
            // 1. التحقق من ملكية الحجز
            if ($user->id !== $booking->user_id) {
                return Response::deny(__('validation.custom.booking.not_your_booking'));
            }

            // 2. التحقق من حالة الحجز (هل يسمح بالإلغاء؟)
            if (!in_array($booking->status, ['draft', 'confirmed'])) {
                return Response::deny(__('validation.custom.booking.cannot_cancel_current_status'));
            }

            return Response::allow();
        }

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

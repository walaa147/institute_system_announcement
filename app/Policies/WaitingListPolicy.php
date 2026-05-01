<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaitingList;
use Illuminate\Auth\Access\Response;

class WaitingListPolicy
{
    /**
     * Determine whether the user can view any models.
     */
  public function viewAny(User $user)
{
    // السوبر أدمن مسموح له بكل المعاهد
    if ($user->hasRole('super_admin')) {
        return true;
    }

    // السكرتير مسموح له فقط إذا كان يتبع نفس المعهد الخاص بالإعلان
    // سنقوم بالتحقق من هذا داخل الكنترولر أو هنا عبر الريكوست
    return $user->hasRole('secretary');
}

public function view(User $user, WaitingList $waitingList)
{
    if ($user->hasRole('super_admin')) return true;

    // إذا كان صاحب السجل (الطالب)
    if ($user->id === $waitingList->user_id) return true;

    // إذا كان سكرتير، يجب أن يكون معهد السكرتير هو نفسه معهد الإعلان
    if ($user->hasRole('secretary')) {
        $ad = $waitingList->bookable; // جلب الإعلان المرتبط بسجل الانتظار
        return $user->institute_id === $ad->institute_id;
    }

    return false;
}

}

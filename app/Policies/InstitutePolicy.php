<?php

namespace App\Policies;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InstitutePolicy
{
    /**
     * تحديد من يمكنه تحديث بيانات المعهد.
     */
    public function update(User $user, Institute $institute): Response
    {
        // 1. السوبر أدمن مسموح له دائماً
        if ($user->hasRole('super_admin')) {
            return Response::allow();
        }

        // 2. التحقق مما إذا كان سكرتيراً لهذا المعهد
        if ($user->hasRole('secretary')) {
            return $user->institute_id === $institute->id
                ? Response::allow()
                : Response::deny(__('validation.custom.institute.update_permission_denied'));
        }

        return Response::deny(__('validation.custom.institute.update_permission_denied'));
    }

    /**
     * تحديد من يمكنه حذف المعهد.
     */
    public function delete(User $user, Institute $institute): Response
    {
        return $user->hasRole('super_admin')
            ? Response::allow()
            : Response::deny(__('validation.custom.institute.delete_permission_denied'));
    }
}

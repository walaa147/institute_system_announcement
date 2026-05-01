<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
class DepartmentPolicy
{
    public function before(User $user, $ability)
    {
        // إذا كان المستخدم سوبر أدمن، اسمح له بكل شيء (true)
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // إذا لم يكن سوبر أدمن، سيكمل لارافيل الفحص في الدوال بالأسفل
    }
    /**
     * هل يسمح للمستخدم بعرض قسم معين؟
     */
public function view(?User $user, Department $department)
{// جلب المعهد مع التأكد من وجود حقل الحالة حتى لو لم يتم تحميله في الـ $with
    $institute = $department->institute;

    // إذا كان حقل status غير موجود في الكائن المحمل، نقوم بجلب المعهد كاملاً
    if ($institute && !isset($institute->status)) {
        $institute = \App\Models\Institute::find($department->institute_id);
    }

    if ($institute && $institute->status === false) {
        if ($user && $user->institute_id == $department->institute_id) {
            return Response::allow();
        }
        return Response::deny(__('validation.custom.institute.disabled'));
    }

    // 3. فحص القسم
    if ($department->is_active == false) {
        // يسمح فقط لابن المعهد برؤيته
        if ($user && $user->institute_id == $department->institute_id) {
            return Response::allow();
        }
        return Response::deny(__('validation.custom.department.disabled'));
    }

    // 4. إذا كان المعهد والقسم مفعّلين، الجميع (حتى الزوار) يشاهدون
    return Response::allow();
}

public function create(User $user)
    {
        return $user->isStatusAdmin()
            ? Response::allow()
            : Response::deny(__('validation.custom.department.create_forbidden'));
    }

    public function update(User $user, Department $department)
    {
        return $user->institute_id === $department->institute_id
            ? Response::allow()
            : Response::deny(__('validation.custom.department.update_forbidden'));
    }

    public function delete(User $user, Department $department)
    {
        return $user->institute_id === $department->institute_id
            ? Response::allow()
            : Response::deny(__('validation.custom.department.delete_forbidden'));
    }
}

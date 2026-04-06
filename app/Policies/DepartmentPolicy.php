<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

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
    public function view(User $user, Department $department): bool
    {
        // السكرتير يرى فقط أقسام معهده
        return $user->institute_id === $department->institute_id;
    }

    /**
     * هل يسمح للمستخدم بتحديث قسم معين؟
     */
    public function update(User $user, Department $department): bool
    {
        return $user->institute_id === $department->institute_id;
    }

    /**
     * هل يسمح للمستخدم بحذف قسم معين؟
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->institute_id === $department->institute_id;
    }
    public function create(User $user): bool
{
    // يجب أن تعيد true لكي يسمح لك بالدخول
    // تأكد أن ميثود isStatusAdmin() تعيد true لهذا المستخدم
    return $user->isStatusAdmin();
}
}

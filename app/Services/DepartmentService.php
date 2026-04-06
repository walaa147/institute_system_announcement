<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DepartmentService
{
    public function store(array $data): Department
    {
        $user = request()->user();
        // إذا كان سكرتير، نأخذ المعهد من حسابه غصباً عنه (للأمان)
    // 1. إذا كان سكرتير أو أدمن معهد، نأخذ المعهد من حسابه إجبارياً (للأمان)
    if ($user->hasRole('secretary') || $user->hasRole('admin')) {
        if (!$user->institute_id) {
            throw new \Exception("هذا الحساب سكرتير ولكن غير مرتبط بمعهد في قاعدة البيانات.");
        }
        $data['institute_id'] = $user->institute_id;
    }
    // 2. إذا كان سوبر أدمن، نتحقق أنه أرسل institute_id في الطلب (Postman)
    elseif ($user->hasRole('super_admin')) {
        if (!isset($data['institute_id'])) {
            throw new \Exception("يجب على السوبر أدمن تحديد رقم المعهد (institute_id) في الطلب.");
        }
        // هنا سيبقى الـ institute_id كما هو مرسل في مصفوفة $data
    }

    else {
        throw new \Exception("لا تملك الصلاحية لتحديد معهد لهذا القسم.");
    }
        return DB::transaction(function () use ($data) {

            $data['slug'] = Str::slug($data['name_en'] ?? $data['name_ar']) . '-' . Str::random(6);
            return Department::create($data);
        });
    }

    public function update(Department $department, array $data): Department
    {
        return DB::transaction(function () use ($department, $data) {
            if (isset($data['name_ar']) || isset($data['name_en'])) {
                $data['slug'] = Str::slug($data['name_en'] ?? $data['name_ar']) . '-' . Str::random(6);
            }
            $department->update($data);
            return $department->refresh();
        });
    }

    public function delete(Department $department): bool
    {
        return DB::transaction(function () use ($department) {
            // منع الحذف إذا كان هناك كورسات أو دبلومات
            if ($department->courses()->exists() || $department->diplomas()->exists()) {
                throw new \Exception(__('validation.custom.department.has_related_data'));
            }
            return $department->delete();
        });
    }

    public function toggleStatus(Department $department): Department
    {
        $department->update(['is_active' => !$department->is_active]);
        return $department->refresh();
    }
}

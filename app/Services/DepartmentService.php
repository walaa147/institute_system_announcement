<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DepartmentService
{
    public function store(array $data): Department
    {
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

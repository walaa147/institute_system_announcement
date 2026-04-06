<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdvertisementService
{
    public function store(array $data): Advertisement
    {
        $user = request()->user();

        // 1. الأمان: التحقق من أن القسم المختار يتبع لمعهد المستخدم
        $this->verifyDepartmentBelongsToInstitute($data['department_id'], $user);

        // 2. تعيين المعهد والمسؤول عن الإنشاء تلقائياً
        if (!$user->hasRole('super_admin')) {
            $data['institute_id'] = $user->institute_id;
        } elseif (!isset($data['institute_id'])) {
            throw new \Exception("يجب على السوبر أدمن تحديد رقم المعهد.");
        }

        $data['created_by'] = $user->id;

        // 3. توليد Slug فريد
        $data['slug'] = Str::slug($data['title_ar']) . '-' . Str::random(6);

        // 4. معالجة رفع الصورة (إذا وجدت)
        if (request()->hasFile('image_path')) {
            $data['image_path'] = request()->file('image_path')->store('advertisements', 'public');
        }

        return DB::transaction(function () use ($data) {
            return Advertisement::create($data);
        });
    }

    public function update(Advertisement $advertisement, array $data): Advertisement
    {
        $user = request()->user();

        // الأمان: إذا تم تغيير القسم، نتأكد من ملكيته
        if (isset($data['department_id'])) {
            $this->verifyDepartmentBelongsToInstitute($data['department_id'], $user);
        }

        // معالجة الصورة الجديدة وحذف القديمة
        if (request()->hasFile('image_path')) {
            if ($advertisement->image_path) {
                Storage::disk('public')->delete($advertisement->image_path);
            }
            $data['image_path'] = request()->file('image_path')->store('advertisements', 'public');
        }

        if (isset($data['title_ar'])) {
            $data['slug'] = Str::slug($data['title_ar']) . '-' . Str::random(6);
        }

        return DB::transaction(function () use ($advertisement, $data) {
            $advertisement->update($data);
            return $advertisement->refresh();
        });
    }

    public function delete(Advertisement $advertisement): bool
    {
        return DB::transaction(function () use ($advertisement) {
            // حذف الصورة من التخزين عند حذف السجل نهائياً
            if ($advertisement->image_path) {
                Storage::disk('public')->delete($advertisement->image_path);
            }
            return $advertisement->delete();
        });
    }
    public function toggleStatus(Advertisement $advertisement): Advertisement
    {
        $advertisement->update(['is_active' => !$advertisement->is_active]);
        return $advertisement->refresh();
    }

    /**
     * وظيفة أمان خاصة: التحقق من أن القسم يتبع للمعهد
     */
    private function verifyDepartmentBelongsToInstitute($departmentId, $user)
    {
        // السوبر أدمن يتخطى هذا الفحص
        if ($user->hasRole('super_admin')) return;

        $exists = Department::where('id', $departmentId)
            ->where('institute_id', $user->institute_id)
            ->exists();

        if (!$exists) {
            throw new \Exception("عذراً، هذا القسم لا يتبع لمعهدك. لا يمكنك إضافة إعلان فيه.");
        }
    }
}

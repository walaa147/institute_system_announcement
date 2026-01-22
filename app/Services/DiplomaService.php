<?php

namespace App\Services;

use App\Models\Diploma;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DiplomaService
{
    public function store(array $data): Diploma
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::where('user_id', Auth::id())->first();
            if (!$employee) {
                throw new \Exception("عذراً، المستخدم الحالي ليس لديه سجل موظف.");
            }

            $data['created_by'] = $employee->id;
            $data['updated_by'] = $employee->id;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_open'] = $data['is_open'] ?? true;

            if (isset($data['image']) && $data['image']->isValid()) {
                $data['photo_path'] = $data['image']->store('diplomas', 'public');
            }

            // استخراج معرفات الكورسات قبل إنشاء الدبلوم
            $courseIds = $data['course_ids'] ?? [];
            unset($data['course_ids'], $data['image']);

            $diploma = Diploma::create($data);

            // ربط الكورسات بالدبلوم (Many-to-Many)
            $diploma->courses()->sync($courseIds);

            return $diploma->load('courses'); // تحميل الكورسات المرتبطة قبل الرد
        });
    }

    public function update(Diploma $diploma, array $data): Diploma
    {
        return DB::transaction(function () use ($diploma, $data) {
            $employee = Employee::where('user_id', Auth::id())->first();
            if ($employee) { $data['updated_by'] = $employee->id; }

            if (isset($data['image']) && $data['image']->isValid()) {
                if ($diploma->photo_path) {
                    Storage::disk('public')->delete($diploma->photo_path);
                }
                $data['photo_path'] = $data['image']->store('diplomas', 'public');
            }

            if (isset($data['course_ids'])) {
                $diploma->courses()->sync($data['course_ids']);
            }

            unset($data['image'], $data['course_ids']);
            $diploma->update($data);

            return $diploma->refresh()->load('courses');
        });
    }

    public function delete(Diploma $diploma): bool
    {
        return DB::transaction(function () use ($diploma) {
            if ($diploma->photo_path) {
                Storage::disk('public')->delete($diploma->photo_path);
            }
            // لارافل سيحذف قيود الجدول الوسيط تلقائياً بسبب onDelete('cascade')
            return $diploma->delete();
        });
    }
}

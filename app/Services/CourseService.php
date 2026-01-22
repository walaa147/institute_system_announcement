<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CourseService
{
    public function store(array $data): Course
    {
        return DB::transaction(function () use ($data) {

            // 1. جلب سجل الموظف المرتبط بالمستخدم الحالي
            $employee = Employee::where('user_id', Auth::id())->first();

            // التحقق من وجود الموظف لتجنب خطأ الـ Foreign Key (created_by)
            if (!$employee) {
                throw new \Exception("عذراً، هذا المستخدم ليس لديه سجل في جدول الموظفين. لا يمكنه إنشاء كورس.");
            }

            // 2. ربط الكورس بـ ID الموظف
            $data['created_by'] = $employee->id;
            $data['updated_by'] = $employee->id;

            // 3. التعامل مع الصورة
            if (isset($data['image']) && $data['image']->isValid()) {
                $data['photo_path'] = $data['image']->store('courses', 'public');
            }
            unset($data['image']);
$data['is_active'] = $data['is_active'] ?? true;
$data['is_open'] = $data['is_open'] ?? true;

            // 4. إنشاء الكورس
            return Course::create($data);
            return $course->refresh();
        });
    }

    public function update(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data) {

            // جلب الموظف الذي يقوم بالتحديث
            $employee = Employee::where('user_id', Auth::id())->first();

            if ($employee) {
                $data['updated_by'] = $employee->id;
            }

            // تحديث الصورة وحذف القديمة
            if (isset($data['image']) && $data['image']->isValid()) {
                if ($course->photo_path) {
                    Storage::disk('public')->delete($course->photo_path);
                }
                $data['photo_path'] = $data['image']->store('courses', 'public');
            }

            unset($data['image']);

            $course->update($data);
            return $course;
        });
    }

    public function delete(Course $course): bool|null
    {
        return DB::transaction(function () use ($course) {
            if ($course->photo_path) {
                Storage::disk('public')->delete($course->photo_path);
            }
            return $course->delete();
        });
    }
}

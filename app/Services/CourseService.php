<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
class CourseService
{
    public function store(array $data): Course
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->verifyDepartmentBelongsToInstitute($data['department_id'], $user);

        if (!$user->hasRole('super_admin')) {
            $data['institute_id'] = $user->institute_id;
        } elseif (!isset($data['institute_id'])) {
            throw new \Exception("يجب على السوبر أدمن تحديد رقم المعهد.");
        }

        $data['created_by'] = $user->id;

        //  توليد Code و Slug فريد
        $data['code'] = $this->generateUniqueCode();
        $data['slug'] = Str::slug($data['name_ar'] ?? $data['title_ar']) . '-' . strtolower($data['code']);

        //  معالجة رفع الصورة
        if (request()->hasFile('photo_path')) {
            $data['photo_path'] = request()->file('photo_path')->store('courses', 'public');
        }

        return DB::transaction(function () use ($data) {
            return Course::create($data);
        });
    }

    public function update(Course $course, array $data): Course
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        //  إذا تم تغيير القسم، نتأكد من ملكيته
        if (isset($data['department_id'])) {
            $this->verifyDepartmentBelongsToInstitute($data['department_id'], $user);
        }

        $data['updated_by'] = $user->id;

        //معالجة الصورة الجديدة وحذف القديمة
        if (request()->hasFile('photo_path')) {
            if ($course->photo_path) {
                Storage::disk('public')->delete($course->photo_path);
            }
            $data['photo_path'] = request()->file('photo_path')->store('courses', 'public');
        }

        // تحديث الـ Slug إذا تم تغيير الاسم
        if (isset($data['name_ar']) || isset($data['title_ar'])) {
            $slugBase = $data['name_ar'] ?? $data['title_ar'];
            $data['slug'] = Str::slug($slugBase) . '-' . strtolower($course->code);
        }

        return DB::transaction(function () use ($course, $data) {
            $course->update($data);
            return $course->refresh();
        });
    }

    public function delete(Course $course): bool
    {
        return DB::transaction(function () use ($course) {
            // حذف ناعم (Soft Delete) فقط
            return $course->delete();
        });
    }

    public function toggleStatus(Course $course): Course
    {
        $course->update(['is_active' => !$course->is_active]);
        return $course->refresh();
    }

    /**
     * التحقق من أن القسم يتبع للمعهد
     */
    private function verifyDepartmentBelongsToInstitute($departmentId, $user)
    {
        if ($user->hasRole('super_admin')) return;

        $exists = Department::where('id', $departmentId)
            ->where('institute_id', $user->institute_id)
            ->exists();

        if (!$exists) {
            throw new \Exception("عذراً، هذا القسم لا يتبع لمعهدك. لا يمكنك إضافة دورة فيه.");
        }
    }

    /**
     * دالة مساعدة لتوليد كود فريد مكون من 8 أحرف وأرقام للدورة
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Course::where('code', $code)->exists());

        return $code;
    }
}

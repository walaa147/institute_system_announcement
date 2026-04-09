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


    /**
     * جلب الكورسات للزوار والطلاب مع دعم الفلترة والبحث
     */
    public function getPublicCourses(array $filters)
    {
        // استخدام when للتحقق الذكي: إذا وجد الفلتر يتم تطبيق الاستعلام، وإلا يتم تجاهله
        return Course::with(['department.institute', 'likes']) // جلب العلاقات لتجنب N+1
            ->withCount('likes')
            ->where('is_active', true) // الزائر يرى الكورسات النشطة فقط
            ->when($filters['search'] ?? null, function ($query, $search) {
                // البحث في الاسم العربي، الإنجليزي، أو العنوان
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'LIKE', "%{$search}%")
                      ->orWhere('name_en', 'LIKE', "%{$search}%")
                      ->orWhere('title_ar', 'LIKE', "%{$search}%");
                });
            })
            ->when($filters['department_id'] ?? null, function ($query, $departmentId) {
                // الفلترة برقم القسم
                $query->where('department_id', $departmentId);
            })
            ->latest()
            ->paginate(15);
    }

    /**
     * تفاعل الطالب: إضافة أو إزالة الإعجاب (Toggle Like)
     */
    public function toggleLike(Course $course, $user): array
    {
        // التحقق مما إذا كان المستخدم قد أعجب بالكورس مسبقاً
        // نستخدم العلاقة المتعددة الأشكال (Polymorphic) بشكل مباشر
        $existingLike = $course->likes()->where('user_id', $user->id)->first();

        if ($existingLike) {
            // إذا وجدنا إعجاباً، نقوم بحذفه (سحب الإعجاب)
            $existingLike->delete();
            return ['is_liked' => false];
        }

        // إذا لم نجد إعجاباً، نقوم بإنشائه
        $course->likes()->create([
            'user_id' => $user->id
            // لاحظ أننا لا نمرر likeable_type أو likeable_id
            // لأن لارافل سيقوم بتعبئتها تلقائياً بفضل العلاقة
        ]);

        return ['is_liked' => true];
    }
}

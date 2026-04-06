<?php

namespace App\Services;

use App\Models\Institute;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;


use Illuminate\Support\Facades\Storage;
// use App\Models\AuditLog; // سنحتاج هذا الموديل لتسجيل الأحداث

class InstituteService
{
    public function __construct(protected ImageService $imageService)
    {
    }
    public function store(array $data): Institute
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] =Str::slug($data['name_en'] ?? $data['name_ar']). '-' . Str::random(6); // توليد سلاگ فريد باستخدام الاسم وراندوم
           // 2. معالجة اللوجو (Logo)
        if (isset($data['logo'])) {
            $data['logo'] = $this->imageService->updateImage($data['logo'], null, 'institutes/logos');
        }

        // 3. معالجة غلاف المعهد (Cover Photo)
        if (isset($data['cover_photo'])) {
            $data['cover_photo'] = $this->imageService->updateImage($data['cover_photo'], null, 'institutes/covers');
        }
        $data['lat'] = $data['lat'] ?? null;
            $data['lng'] = $data['lng'] ?? null;
        $institute = Institute::create($data);

        $this->refreshPriority($institute);


        return $institute->refresh(); // إعادة جلب المعهد بعد الحفظ للتأكد من تحديث الحقول المحسوبة مثل الأولوية
           // unset($data['logo']);
// 3. توثيق الحدث في سجل التدقيق المركزي
            // AuditLog::create([
            //     'action' => 'create_institute',
            //     'description' => "تم إنشاء معهد جديد باسم: {$institute->name}",
            //     'user_id' => auth()->id(),
            // ]);

        });
    }

    public function update(Institute $institute, array $data): Institute
    {
        return DB::transaction(function () use ($institute, $data) {
         if (isset($data['logo'])&& $data['logo'] instanceof UploadedFile) {
                $data['logo'] = $this->imageService->updateImage(
                    $data['logo'], $institute->logo, 'institutes/logos');
            }
            if (isset($data['cover_photo'])&& $data['cover_photo'] instanceof UploadedFile) {
                $data['cover_photo'] = $this->imageService->updateImage($data['cover_photo'], $institute->cover_photo, 'institutes/covers');
            }
            if(isset($data['name_ar']) || isset($data['name_en'])) {
                $nameForSlug = $data['name_en'] ?? $data['name_ar'] ?? $institute->name_ar;
                $data['slug'] = Str::slug($nameForSlug) . '-' . Str::random(6); // تحديث السلاگ إذا تم تغيير الاسم
            }


            $institute->update($data);
            $this->refreshPriority($institute);

             // 3. توثيق الحدث في سجل التدقيق المركزي
            return $institute->refresh(); // إعادة جلب المعهد بعد التحديث للتأكد من تحديث الحقول المحسوبة مثل الأولوية
        });
    }

    public function delete(Institute $institute): bool
    {
        return DB::transaction(function () use ($institute) {
           if ($institute->logo) {
                $this->imageService->deleteImage($institute->logo);
            }
            if ($institute->cover_photo) {
                $this->imageService->deleteImage($institute->cover_photo);
            }
            return $institute->delete();
        });
    }
    // دالة لتحديث الأولوية الذكية بناءً على النقاط وسرعة الرد
    public function refreshPriority(Institute $institute): void
    {
        // معادلة الأولوية: (النقاط / 10) + (عامل سرعة الرد) + (أي تميز يدوي)
        $pointsWeight = $institute->points_balance / 10;

        // حساب وزن سرعة الرد: كلما قل الوقت زاد الوزن (بحد أقصى 20 نقطة تميز)
        $responseTimeWeight = 0;
        if ($institute->avg_response_time > 0) {
            $responseTimeWeight = max(0, 50 - ($institute->avg_response_time / 5));
        }

        // تحديث الحقل الحاكم للعرض
        $institute->update([
            'priority_level' => (int) ($pointsWeight + $responseTimeWeight)
        ]);

    }
    /**
 * تغيير حالة المعهد (تفعيل/تعطيل)
 */
public function toggleStatus(Institute $institute): Institute
{
    $institute->update([
        'status' => !$institute->status // إذا كان 1 يصبح 0 والعكس
    ]);
    $this->refreshPriority($institute); // تحديث الأولوية بعد تغيير الحالة


    return $institute->refresh();
}
}

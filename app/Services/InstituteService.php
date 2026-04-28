<?php

namespace App\Services;

use App\Models\Institute;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Services\ImageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


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
// داخل دالة store بعد إنشاء المعهد
Log::info("تم إنشاء معهد جديد", [
    'id' => $institute->id,
    'name' => $institute->name_ar,
    'by_user' =>Auth::id()
]);

        return $institute->refresh(); // إعادة جلب المعهد بعد الحفظ للتأكد من تحديث الحقول المحسوبة مثل الأولوية

        });
    }

    public function update(Institute $institute, array $data): Institute
    {
        return DB::transaction(function () use ($institute, $data) {
            /** @var \App\Models\User $user */
            $user = auth('sanctum')->user();

            // حماية الحقول الحساسة من التعديل غير المصرح به
            if (!$user->hasRole('super_admin')) {
                unset($data['commission_rate'], $data['status'], $data['points_balance']);
            }

            if (isset($data['logo'])&& $data['logo'] instanceof UploadedFile) {
                $data['logo'] = $this->imageService->updateImage(
                    $data['logo'], $institute->logo, 'institutes/logos');
            }
            if (isset($data['cover_photo'])&& $data['cover_photo'] instanceof UploadedFile) {
                $data['cover_photo'] = $this->imageService->updateImage($data['cover_photo'], $institute->cover_photo, 'institutes/covers');
            }
            unset($data['slug']); // لا تسمح بتغيير السلاگ لأنه يؤثر على الروابط
               if (!array_key_exists('lat', $data) || $data['lat'] === null) {
    unset($data['lat']);
}
if (!array_key_exists('lng', $data) || $data['lng'] === null) {
    unset($data['lng']);
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
            if ($institute->bookings()->whereIn('status', ['confirmed', 'pending'])->exists()) {
             throw new \Exception(__('validation.custom.institute.has_active_bookings'));
        }
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
            $responseTimeWeight = max(0, 100 - ($institute->avg_response_time));
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
/**
 * تحديث نسبة العمولة يدوياً بواسطة السوبر أدمن
 */
public function updateCommissionRate(Institute $institute, float $newRate): Institute
{
    $institute->update(['commission_rate' => $newRate]);
    return $institute->refresh();
}

/**
 * إضافة نقاط للمعهد (سواء بالشراء أو كمكافأة)
 */
public function addPoints(Institute $institute, int $points): Institute
{
    $institute->increment('points_balance', $points);

    // بعد إضافة النقاط، يجب إعادة حساب مستوى الأولوية فوراً
    $this->refreshPriority($institute);

    return $institute->refresh();
}

/**
 * دالة مساعدة لحساب صافي العمولة من مبلغ معين
 */
public function calculateCommissionAmount(Institute $institute, float $amount): float
{
    return ($amount * $institute->commission_rate) / 100;
}
}

<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use App\Services\ImageService;

use Illuminate\Support\Facades\Storage;

class AdvertisementService
{
    protected ImageService $imageService;
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
public function store(array $data): Advertisement
{
    $user = request()->user();

    // 1. تحديد الموديل (كورس أو دبلوم) بناءً على الـ type
    $type = $data['type'] ?? 'course';
    $modelClass = ($type === 'diploma') ? \App\Models\Diploma::class : \App\Models\Course::class;

    // 2. جلب الكائن المرتبط (إذا أرسل المستخدم id)
    $item = null;
    if (!empty($data['advertisable_id'])) {
        $item = $modelClass::find($data['advertisable_id']);
    }

    // 3. تحديد القسم: (من الكورس تلقائياً، أو من المدخلات إذا كان الإعلان مستقلاً)
    $data['department_id'] = $data['department_id'] ?? ($item ? $item->department_id : null);

    // إذا لم يتوفر قسم لا من الكورس ولا من المدخلات، نوقف العملية
    if (empty($data['department_id'])) {
        throw new \Exception(__('validation.custom.advertisement.department_required'));
    }

    // التأكد من ملكية القسم للمعهد
    $this->verifyDepartmentBelongsToInstitute($data['department_id'], $user);

    // 4. سحب البيانات من الكورس (إذا وجد) أو استخدام البيانات المرسلة يدوياً
    if ($item) {
        $data['advertisable_type'] = $modelClass;
        $data['title_ar']       = $data['title_ar'] ?? $item->name_ar;
        $data['title_en']       = $data['title_en'] ?? $item->name_en;
        $data['description_ar'] = $data['description_ar'] ?? ($item->description_ar ?? $item->description);
        $data['description_en'] = $data['description_en'] ?? ($item->description_en ?? $item->description);
        $data['duration']       = $data['duration'] ?? $item->duration;
        $data['start_date']     = $data['start_date'] ?? $item->start_date;
        $data['end_date']       = $data['end_date']   ?? $item->end_date;

        // السعر الأصلي
        $originalPrice = ($modelClass === \App\Models\Course::class) ? $item->price : $item->total_cost;
        $data['price_before_discount'] = $data['price_before_discount'] ?? $originalPrice;

        // الصورة (إذا لم يرفع صورة جديدة)
        if (!request()->hasFile('image_path')) {
            $data['image_path'] = $item->photo_path ?? $item->photo;
        }
    }

    // 5. منطق المعهد (نأخذه من القسم دائماً لضمان الصحة)
    $department = \App\Models\Department::find($data['department_id']);
    $data['institute_id'] = $department->institute_id;

    // 6. الحسابات العامة (للإعلان المرتبط والمستقل)
    $data['published_at'] = $data['published_at'] ?? now();
    if (empty($data['expired_at']) && !empty($data['start_date'])) {
        $data['expired_at'] = \Carbon\Carbon::parse($data['start_date'])->subDay();
    }
    $data['is_active'] = \Carbon\Carbon::parse($data['published_at'])->isPast();

    $priceBefore = $data['price_before_discount'] ?? 0;
    $discount = $data['discount_percentage'] ?? 0;
    $data['price_after_discount'] = $priceBefore - ($priceBefore * ($discount / 100));

    // معالجة الصورة المرفوعة يدوياً
    if (request()->hasFile('image_path')) {
        $data['image_path'] = $this->imageService->updateImage(request()->file('image_path'), null, 'advertisements');
    }

    // الـ Slug والبيانات النهائية
    $data['slug'] = Str::slug($data['title_ar'] ?? 'ad') . '-' . Str::random(6);
    $data['created_by'] = $user->id;

    return DB::transaction(fn() => Advertisement::create($data));
}
       public function update(Advertisement $advertisement, array $data): Advertisement
    {
        $user = request()->user();

        // الأمان: إذا تم تغيير القسم، نتأكد من ملكيته
        if (isset($data['department_id'])) {
            $this->verifyDepartmentBelongsToInstitute($data['department_id'], $user);
        }
        // إعادة حساب الخصم إذا تغير السعر أو النسبة
        if (isset($data['discount_percentage']) || isset($data['price_before_discount'])) {
            $priceBefore = $data['price_before_discount'] ?? $advertisement->price_before_discount;
            $percentage = $data['discount_percentage'] ?? $advertisement->discount_percentage;
            $data['price_after_discount'] = $priceBefore - ($priceBefore * ($percentage / 100));
        }

        // معالجة الصورة الجديدة وحذف القديمة
        if (request()->hasFile('image_path')) {
           // التعديل: الملف الجديد أولاً ثم المسار الحالي للإعلان
$data['image_path'] = $this->imageService->updateImage(
    request()->file('image_path'),
    $advertisement->image_path,    // هنا نمرر المسار القديم ليتم حذفه
    'advertisements'
);
        }

        if (isset($data['title_ar'])) {
            $data['slug'] = Str::slug($data['title_ar']) . '-' . Str::random(6);
        }
        if(isset($data['max_seats']) && $advertisement->current_seats_taken > $data['max_seats']) {
            throw new \Exception(__('validation.custom.advertisement.seats_limit_exceeded'));
        }

        return DB::transaction(function () use ($advertisement, $data) {
            $advertisement->update($data);
            return $advertisement->refresh();
        });
    }

    public function delete(Advertisement $advertisement): bool
    {
        return DB::transaction(function () use ($advertisement) {
            if ($advertisement->bookings()->exists()) {
            throw new \Exception(__('validation.custom.advertisement.has_bookings'));
        }
            // حذف الصورة من التخزين عند حذف السجل نهائياً
            if ($advertisement->image_path) {
                // استخدام الخدمة للحذف بدلاً من Storage المباشر
                $this->imageService->deleteImage($advertisement->image_path);
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
            throw new \Exception(__('validation.custom.department.invalid_department'));
        }
    }

}

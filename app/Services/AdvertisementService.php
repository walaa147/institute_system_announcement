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

    // 1. تحديد النوع تلقائياً (كورس هو الافتراضي)
    $type = $data['type'] ?? 'course';
    $modelClass = ($type === 'diploma') ? \App\Models\Diploma::class : \App\Models\Course::class;

    // 2. جلب الكائن المرتبط (الكورس أو الدبلوم)
    $item = $modelClass::findOrFail($data['advertisable_id']);
    $data['department_id'] = $data['department_id'] ?? $item->department_id;
    $this->verifyDepartmentBelongsToInstitute($data['department_id'], $user);


    // 3. سحب البيانات تلقائياً من الكورس/الدبلوم إلى مصفوفة الإعلان
    $data['advertisable_type'] = $modelClass;
    $data['title_ar']       = $data['title_ar'] ?? $item->name_ar;
    $data['title_en']       = $data['title_en'] ?? $item->name_en;
    $data['description_ar'] = $data['description_ar'] ?? ($item->description_ar ?? $item->description);
    $data['description_en'] = $data['description_en'] ?? ($item->description_en ?? $item->description);
    $data['duration']       = $data['duration'] ?? $item->duration;
    $data['start_date'] = $data['start_date'] ?? $item->start_date;
    $data['end_date']   = $data['end_date']   ?? $item->end_date;
    $data['published_at'] = $data['published_at'] ?? now(); // افتراضياً ينشر الآن
    if (empty($data['expired_at'])) {
        $data['expired_at'] = \Carbon\Carbon::parse($data['start_date'])->subDay(); // انتهاء قبل يوم من بدء الحدث

    }
    $data['is_active'] = \Carbon\Carbon::parse($data['published_at'])->isPast();

    // 4. تحديد السعر الأصلي (حسب نوع الموديل)
    $originalPrice = ($modelClass === \App\Models\Course::class) ? $item->price : $item->total_cost;
    $data['price_before_discount'] = $data['price_before_discount'] ?? $originalPrice;

    // 5. حساب السعر بعد الخصم
    $discount = $data['discount_percentage'] ?? 0;
    $data['price_after_discount'] = $data['price_before_discount'] - ($data['price_before_discount'] * ($discount / 100));

    // 6. معالجة الصورة (إذا لم ترفع صورة جديدة، اسحب صورة الكورس)
    if (!request()->hasFile('image_path')) {
        $data['image_path'] = $item->photo_path ?? $item->photo;
    } else {
       // التعديل: ضع الملف أولاً ثم null للمسار القديم
$data['image_path'] = $this->imageService->updateImage(
    request()->file('image_path'), // المعامل الأول: الملف الجديد
    null,                          // المعامل الثاني: لا يوجد مسار قديم عند الإضافة
    'advertisements'               // المعامل الثالث: المجلد
);
    }

    // 7. تعيين القيم الإدارية والـ Slug
    $data['slug'] = Str::slug($data['title_ar'] ?? 'ad') . '-' . Str::random(6);
    $data['created_by'] = $user->id;
  // 7. تعيين المعهد (منطق خاص للسوبر أدمن والأدمن العادي)
if ($user->hasRole('super_admin')) {
    // السوبر أدمن:
    // 1. نأخذ المعهد الذي أرسله في الـ Request.
    // 2. إذا لم يرسل، نسحبه من الكورس/الدبلوم المرتبط تلقائياً.
    $data['institute_id'] = $data['institute_id'] ?? $item->institute_id;
} else {
    // الأدمن العادي أو السكرتير:
    // نأخذ معهد المستخدم المسجل في حسابه (لضمان الأمان وعدم التلاعب).
    // وإذا كان فارغاً في حسابه، نلجأ لمعهد الكورس.
    $data['institute_id'] = $user->institute_id ?? $item->institute_id;
}

// تأكد من وجود القيمة قبل الإرسال لقاعدة البيانات لتجنب خطأ الـ Integrity Constraint
if (empty($data['institute_id'])) {
    throw new \Exception(__('validation.custom.institute.required'));
}
// التحقق من أن مقاعد الحجز المبكر منطقية
if (isset($data['early_paid_seats_limit']) && $data['early_paid_seats_limit'] > ($data['max_seats'] ?? 100)) {
    throw new \Exception(__('validation.custom.advertisement.early_bird_limit_exceeded'));
}

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

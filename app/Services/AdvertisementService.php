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
    $this->verifyDepartmentBelongsToInstitute($data['department_id'], $user);

    // السحب التلقائي الذكي
    if (!empty($data['advertisable_id']) && !empty($data['advertisable_type'])) {
        $related = $data['advertisable_type']::find($data['advertisable_id']);

        if ($related) {
            // سحب البيانات الأساسية إذا كانت فارغة
            $data['title_ar'] = $data['title_ar'] ?? $related->name_ar;
            $data['title_en'] = $data['title_en'] ?? $related->name_en;
            $data['description_ar'] = $data['description_ar'] ?? $related->description;
            $data['description_en'] = $data['description_en'] ?? $related->description;
            $data['duration'] = $data['duration'] ?? $related->duration;

            // التعامل مع السعر حسب نوع المورد (كورس أو دبلوم)
            $price = ($data['advertisable_type'] === 'App\Models\Course') ? $related->price : $related->total_cost;
            $data['price_before_discount'] = $data['price_before_discount'] ?? $price;

            // سحب الصورة إذا لم يتم رفع صورة جديدة
            if (!request()->hasFile('image_path') && $related->photo_path) {
                $data['image_path'] = $related->photo_path;
            }
        }
    }
    if(request()->hasFile('image_path')) {
        $data['image_path'] = $this->imageService->updateImage(null, request()->file('image_path'), 'advertisements');
    }

    // تعيين القيم التلقائية
    $data['slug'] = Str::slug($data['title_ar'] ?? 'ad') . '-' . Str::random(6);
    $data['created_by'] = $user->id;
    $data['institute_id'] = $user->hasRole('super_admin') ? $data['institute_id'] : $user->institute_id;
    $data['published_at'] = $data['published_at'] ?? now();

    if (request()->hasFile('image_path')) {
        $data['image_path'] = request()->file('image_path')->store('advertisements', 'public');
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
            throw new \Exception(__('validation.custom.department.invalid_department'));
        }
    }
}

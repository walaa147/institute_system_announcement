<?php

namespace App\Http\Resources;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'description' => $this->description,
            'price' => (float) $this->price,

            'image_url' => $this->photo_path ? url('storage/' . $this->photo_path) : null,

            'is_open' => $this->is_open,
            'is_active' =>  $this->is_active,
// ... داخل دالة toArray
// ...

// حقول المفضلة الجديدة
'like_count' => $this->likes_count ?? 0,
'is_liked' => $this->when(Auth::check(), function () {
    // نعود للتحقق من التحميل لتجنب N+1
    if ($this->resource->relationLoaded('likes')) {
        // نستخدم isNotEmpty الذي ثبت أنه يعمل بكفاءة
        return $this->likes->where('user_id', Auth::id())->isNotEmpty();
    }
    return false;
}),
// ...

// ...
            // بيانات العلاقات
            'department_id' => $this->department_id,
            'department_name' => $this->department?->name_ar,
            'institute_name' => $this->department?->institute?->name_ar,

            // بيانات التعقب
            'created_by_name' => $this->creator?->name_ar ?? 'غير معروف',
        'updated_by_name' => $this->updater?->name_ar ?? 'لم يتم التعديل',


            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

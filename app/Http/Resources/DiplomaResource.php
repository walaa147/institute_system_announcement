<?php

namespace App\Http\Resources;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class DiplomaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'total_cost' => (float) $this->total_cost,
            'image_url' => $this->photo_path ? url('storage/' . $this->photo_path) : null,
            'is_open' => $this->is_open,
            'is_active' =>$this->is_active,
// حقول المفضلة الجديدة
'likes_count' => $this->whenLoaded('likes', function () {
    // استخدم likes_count إذا كنت تستخدم withCount
    return $this->likes_count ?? $this->likes->count();
}),
'is_liked' => $this->when(Auth::check(), function () {
    // تحقق ما إذا كان المستخدم الحالي قد وضعها في المفضلة
    return $this->likes->contains('user_id', Auth::id());
}),
            // بيانات المعهد
            'institute_name' => $this->institute?->name_ar,

            // قائمة الكورسات داخل الدبلوم
            'courses' => CourseResource::collection($this->whenLoaded('courses')),

            'created_by_name' => $this->creator?->name_ar ?? 'غير معروف',
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

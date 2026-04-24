<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DiplomaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->slug,

            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,

            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,

            'total_cost' => (float) $this->total_cost,

            'image_url' => $this->photo_url,

            'is_active' => $this->is_active,
            'is_available' => $this->is_available,

            'duration' => $this->duration,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,

            'like_count' => $this->likes_count ?? 0,

            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'name_ar' => $this->department->name_ar,
                ];
            }),

            'institute' => $this->whenLoaded('institute', function () {
                return [
                    'id' => $this->institute->id,
                    'name_ar' => $this->institute->name_ar,
                ];
            }),

            'courses' => CourseResource::collection(
                $this->whenLoaded('courses')
            ),

            'created_by_name' => $this->creator?->name_ar ?? 'غير معروف',
            'updated_by_name' => $this->updater?->name_ar ?? 'لم يتم التعديل',

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
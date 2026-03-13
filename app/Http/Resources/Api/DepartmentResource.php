<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class DepartmentResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var \App\Models\User $user */
        $user = Auth('sanctum')->user();

        return [
            'id'             => $this->id,
            'slug'           => $this->slug,
            'name_ar'        => $this->name_ar,
            'name_en'        => $this->name_en,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'is_active'      => (boolean) $this->is_active,

            // عرض المعهد التابع له القسم (اختياري إذا تم تحميله)
            'institute' => [
                'id'      => $this->institute_id,
                'name_ar' => $this->whenLoaded('institute', fn() => $this->institute->name_ar),
                'name_en' => $this->whenLoaded('institute', fn() => $this->institute->name_en),
            ],

            // إحصائيات سريعة - تظهر فقط إذا استخدمت withCount في الكنترولر
            'stats' => [
                'courses_count' => $this->whenCounted('courses'),
                'diplomas_count' => $this->whenCounted('diplomas'),
                'ads_count'     => $this->whenCounted('advertisements'),
            ],

            // دمج بيانات إدارية فقط إذا كان المستخدم سوبر أدمن أو سكرتير (مثال للخصوصية)
            $this->mergeWhen(($user instanceof \App\Models\User) && $user->isStatusAdmin(), [
                'internal_notes' => $this->notes ?? '', // مثال لحقل خاص بالإدارة
                'can_delete'     => !$this->courses()->exists(), // مثال لمنطق برمجي للإدارة
            ]),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

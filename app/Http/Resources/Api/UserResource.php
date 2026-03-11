<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // تخزين البروفايل في متغير لتجنب تكرار الاستعلام وتحسين الأداء
        $profile = $this->profile;

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,

            // استخدام optional() يضمن أنه إذا كان البروفايل null سيعود null ولن ينهار الكود
            'full_name_ar'      => optional($profile)->full_name_ar,
            'full_name_en'      => optional($profile)->full_name_en,
            'phone_number'      => optional($profile)->phone_number,
            'gender'            => optional($profile)->gender,
            'city'              => optional($profile)->city,
            'address'           => optional($profile)->address,
            'fcm_token'         => optional($profile)->fcm_token,

            // تحويل منطقي آمن
            'profile_completed' => (bool) (optional($profile)->profile_completed ?? false),

            // رابط الصورة الشخصية مع تحقق مزدوج
            'avatar'            => ($profile && $profile->logo)
                                    ? asset('storage/' . $profile->logo)
                                    : null,

            // بيانات المعهد المرتبط
            'institute'         => $this->whenLoaded('institute', function() {
                if (!$this->institute) return null;
                return [
                    'id'        => $this->institute->id,
                    'name_ar'   => $this->institute->name_ar,
                    'name_en'   => $this->institute->name_en,
                    'is_active' => (bool) $this->institute->status,
                ];
            }),

            'created_at'        => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

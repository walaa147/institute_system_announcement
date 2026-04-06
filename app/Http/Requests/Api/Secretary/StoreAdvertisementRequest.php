<?php

namespace App\Http\Requests\Api\Secretary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreAdvertisementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // السماح للسكرتارية والأدمن والسوبر أدمن
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return Auth::check() && ($user->isStatusAdmin() || $user->isStatusSuperAdmin());
    }

    public function rules(): array
    {
        return [
            // الحقول الأساسية
            'title_ar' => 'required|string|max:150',
            'title_en' => 'nullable|string|max:150',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',

            // الربط المتعدد (Morph)
            'advertisable_id' => 'nullable|integer',
            'advertisable_type' => [
                'nullable',
                'string',
                Rule::in(['App\Models\Course', 'App\Models\Diploma'])
            ],

            // حقول المدرب والصورة
            'trainer_name' => 'nullable|string|max:100',
            'image_path' => 'nullable|image|max:2048', // للتحقق من الصورة

            // الأسعار والخصومات
            'price_before_discount' => 'nullable|numeric|min:0',
            'price_after_discount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|between:0,100',
            'discount_expiry' => 'nullable|date|after:now',
            'early_paid_price' => 'nullable|numeric|min:0',
            'is_free' => 'sometimes|boolean',

            // المقاعد والتواريخ
            'max_seats' => 'nullable|integer|min:1',
            'event_date' => 'nullable|date|after_or_equal:today',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'expired_at' => 'nullable|date|after:now',

            // حقول إضافية
            'location' => 'nullable|string|max:255',
            'link' => 'nullable|url',
            'is_open_for_booking' => 'sometimes|boolean',
        ];
    }
}

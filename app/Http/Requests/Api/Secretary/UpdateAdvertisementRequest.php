<?php

namespace App\Http\Requests\Api\Secretary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateAdvertisementRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مخولاً لإجراء هذا الطلب.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // التحقق من أن المستخدم مسجل دخول ولديه رتبة إدارية (سوبر أدمن، أدمن، أو سكرتير)
        return Auth::check() && ($user->isStatusAdmin() || $user->isStatusSuperAdmin());
    }

    /**
     * الحصول على قواعد التحقق التي تنطبق على الطلب.
     */
    public function rules(): array
    {
        return [
            // المعلومات الأساسية (استخدام sometimes يعني الحقل مطلوب فقط إذا تم إرساله)
            'title_ar'            => 'sometimes|nullable|string|max:150',
            'title_en'            => 'nullable|string|max:150',
            'description_ar'      => 'sometimes|nullable|string',
            'description_en'      => 'nullable|string',
            'department_id'       => 'sometimes|exists:departments,id',

            // الربط المتعدد (Morph) - لتغيير الكورس أو الدبلوم المرتبط
            'advertisable_id'     => 'nullable|integer',
            'advertisable_type'   => [
                'nullable',
                'string',
                Rule::in(['App\Models\Course', 'App\Models\Diploma'])
            ],

            // إدارة المقاعد (مهم جداً)
            'max_seats' => [
            'sometimes',
            'integer',
            'min:1',
           function ($attribute, $value, $fail) {
    $ad = $this->route('advertisement');

    // إذا تم تمرير الـ ID فقط بدلاً من الـ Model Binding
    if (is_scalar($ad)) {
        $ad = \App\Models\Advertisement::find($ad);
    }

    if ($ad && $value < $ad->current_seats_taken) {
        $fail(__('validation.custom.advertisement.max_seats_less_than_taken'));
    }
},
        ],
            'current_seats_taken' => 'nullable|integer|min:0|lte:max_seats', // يجب ألا يتجاوز المقاعد المتاحة

            // حقول المدرب والصورة
            'trainer_name'        => 'nullable|string|max:100',
            'image_path'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            // الأسعار والخصومات
            'price_before_discount' => 'nullable|numeric|min:0',
            'price_after_discount'  => 'nullable|numeric|min:0',
            'discount_percentage'   => 'nullable|numeric|between:0,100',
            'discount_expiry'       => 'nullable|date',
            'early_paid_price'      => 'nullable|numeric|min:0',
            'is_free'               => 'sometimes|boolean',

            // التواريخ والوقت
            'event_date'            => 'nullable|date',
            'start_date'            => 'nullable|date',
            'end_date'              => 'nullable|date|after_or_equal:start_date',
            'expired_at'            => 'nullable|date',
            'published_at'          => 'nullable|date',
            'duration'              => 'nullable|string|max:50',

            // الحالات والروابط
            'is_active'             => 'sometimes|boolean',
            'is_open_for_booking'   => 'sometimes|boolean',
            'has_certificate'       => 'sometimes|boolean',
            'location'              => 'nullable|string|max:255',
            'link'                  => 'nullable|url',
        ];
    }

    /**
     * تخصيص رسائل الخطأ (اختياري)
     */

}

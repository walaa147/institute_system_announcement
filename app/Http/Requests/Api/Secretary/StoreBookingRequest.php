<?php

namespace App\Http\Requests\Api\Secretary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation()
    {
        // هنا السر: نحن نجبر النظام على اعتبار النوع 'Advertisement'
        // حتى لو لم يرسله المستخدم في الطلب
        $this->merge([
            'user_id' => Auth::id(),
            'bookable_type' => 'App\Models\Advertisement',
        ]);
    }

    public function rules(): array
    {
        return [
            // الآن لا نحتاج لفحص النوع من المدخلات لأنه مثبت فوق
            'bookable_id' => [
                'required',
                'integer',
                'exists:advertisements,id', // نتحقق مباشرة من جدول الإعلانات
            ],

            // هذا الحقل مطلوب داخلياً (بسبب الـ merge) لكنه لن يظهر كخطأ للمستخدم
            'bookable_type' => ['required', 'string'],

            'user_id' => [
                'required',
                Rule::unique('bookings')->where(function ($query) {
                    return $query->where('user_id', Auth::id())
                                 ->where('bookable_id', $this->bookable_id)
                                 ->where('bookable_type', 'App\Models\Advertisement'); // نثبته هنا أيضاً
                })
            ]
        ];
    }
    public function messages(): array
{
    return [
        // سيجلب: "لديك طلب حجز مسبق لهذا العنصر."
        'user_id.unique' => __('validation.custom.booking.already_exists'),

        // سيجلب: "الإعلان المطلوب غير موجود." (تأكد من وجود هذا المفتاح في ملفك)
        'bookable_id.exists' => __('validation.custom.advertisement.not_found'),

        // رسالة عامة من ملف التحقق الأساسي
        'bookable_id.required' => __('validation.required', ['attribute' => __('validation.attributes.bookable_id')]),
    ];
}
}

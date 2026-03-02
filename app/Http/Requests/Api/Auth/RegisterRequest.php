<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\ApiBaseRequest;

class RegisterRequest extends ApiBaseRequest
{
    public function authorize(): bool
{
    return true;
}
    public function rules(): array
    {
        return [
            // الاسم مطلوب (سنقوم بتخزينه في جدول المستخدمين والتفاصيل)
            'full_name_ar' => 'required|string|max:150',

            // الإيميل مطلوب إذا لم يتم إرسال رقم هاتف، ويجب أن يكون غير مكرر
            'email' => 'required_without:phone_number|nullable|email|unique:users,email',

            // الهاتف مطلوب إذا لم يتم إرسال إيميل، ويجب أن يكون غير مكرر في جدول التفاصيل
            'phone_number' => 'required_without:email|nullable|string|unique:user_profiles,phone_number',

            // كلمة المرور مع تأكيدها (يجب أن يرسل الموبايل حقل password_confirmation)
            'password' => 'required|string|min:8|confirmed',

            // توكن الإشعارات الخاص بجهاز الموبايل (مهم جداً للمستقبل لإرسال إشعارات FCM)
            'fcm_token' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name_ar.required' => 'يرجى إدخال الاسم الكامل.',
            'email.required_without' => 'يجب إدخال البريد الإلكتروني أو رقم الهاتف.',
            'email.unique' => 'هذا البريد الإلكتروني مسجل مسبقاً.',
            'phone_number.unique' => 'رقم الهاتف هذا مسجل مسبقاً.',
            'password.min' => 'كلمة المرور يجب أن لا تقل عن 8 أحرف.',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين.',
        ];
    }
}

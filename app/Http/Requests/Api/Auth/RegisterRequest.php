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
            'full_name_ar' => 'nullable|string|max:150',
            'name' => 'required|string|max:150', // هذا الحقل الجديد لاسم المستخدم

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


}

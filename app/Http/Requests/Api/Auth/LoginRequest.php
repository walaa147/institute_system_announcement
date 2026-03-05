<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\ApiBaseRequest;


class LoginRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [



            // معرف الدخول (الإيميل أو الهاتف) مطلوب إذا لم يتم إرسال توكن جوجل
            'login_id' => 'required_without:google_token|string',

            // كلمة المرور مطلوبة مع الدخول التقليدي
            'password' => 'required_without:google_token|string',

            // توكن جوجل مطلوب إذا كان الدخول عبر جوجل
            'google_token' => 'required_without:login_id|string',

            // تحديث توكن جهاز الموبايل عند الدخول
            'fcm_token' => 'nullable|string',
        ];
    }


}

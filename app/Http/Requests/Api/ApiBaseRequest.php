<?php

namespace App\Http\Requests\Api;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiBaseRequest extends FormRequest
{
    use ApiResponse;

    // السماح للجميع باستخدام هذا الريكويست مبدئياً
    public function authorize(): bool
    {
        return true;
    }

    /**
     * هذا هو "الفخ" الذي نصطاد فيه أخطاء التحقق قبل أن تصل للمتحكم
     * ونقوم بتغليفها بشكلنا الموحد لكي لا ينهار تطبيق الفلتر
     */
    protected function failedValidation(Validator $validator)
    {
        // نستخدم دالة errorResponse الموجودة في الـ Trait
        throw new HttpResponseException(
            $this->errorResponse('حدث خطأ في البيانات المدخلة', $validator->errors(), 422)
        );
    }
}

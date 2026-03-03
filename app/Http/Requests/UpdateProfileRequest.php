<?php

namespace App\Http\Requests;

use App\Http\Requests\Api\ApiBaseRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }
/**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // استخراج معرف المستخدم الحالي الذي يقوم بالطلب
        $userId = $this->user()->id;

        return [
            'name'     => ['sometimes', 'required', 'string', 'max:255'],
            'email'    => [
                'sometimes',
                'required',
                'email',
                // حماية ذكية: الإيميل يجب أن يكون غير مكرر، باستثناء إيميل المستخدم نفسه
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}

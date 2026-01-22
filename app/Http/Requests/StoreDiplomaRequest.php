<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiplomaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:20|unique:diplomas,code',
            'institute_id' => 'required|exists:institutes,id',
            'title_ar' => 'required|string|max:150',
            'title_en' => 'nullable|string|max:150',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'total_cost' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:5120',
            'is_open' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            // التحقق من أن الكورسات المرسلة موجودة فعلياً في القاعدة
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:courses,id',
        ];
    }
}

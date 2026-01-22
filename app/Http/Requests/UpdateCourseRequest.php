<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // جلب معرف الكورس من الرابط (Route Parameter)
        $courseId = $this->route('course')->id;

        return [
            // أحياناً (sometimes) مطلوب، مع استثناء السجل الحالي من قاعدة التكرار
            'code' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('courses', 'code')->ignore($courseId)
            ],

            'department_id' => 'sometimes|exists:departments,id',

            'title_ar' => 'sometimes|string|max:150',
            'title_en' => 'nullable|string|max:150',
            'name_ar' => 'sometimes|string|max:150',
            'name_en' => 'nullable|string|max:150',

            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',

            'image' => 'nullable|image|max:5120',

            'is_open' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:20|unique:courses,code',
            'department_id' => 'required|exists:departments,id',

            'title_ar' => 'required|string|max:150',
            'title_en' => 'nullable|string|max:150',
            'name_ar' => 'required|string|max:150',
            'name_en' => 'nullable|string|max:150',

            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',

            'image' => 'nullable|image|max:5120', // 5MB max

            'is_open' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

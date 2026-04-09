<?php

namespace App\Http\Requests\Api\Users;

use Illuminate\Foundation\Http\FormRequest;

class FilterCourseRequest extends FormRequest
{

    public function authorize(): bool
    {

        return true;
    }


    public function rules(): array
    {
        return [
            // كلمة البحث (اختيارية، نص، ولا تتجاوز 255 حرف لحماية قاعدة البيانات)
            'search'        => ['nullable', 'string', 'max:255'],

            // فلتر القسم (اختياري، رقم، ويجب أن يكون موجوداً في جدول الأقسام)
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
    }

    /**
     * تخصيص رسائل الخطأ (اختياري لزيادة وضوح الردود للـ Frontend)
     */
    public function messages(): array
    {
        return [
            'department_id.exists' => __('validation.custom.department.not_found') ?? 'القسم المحدد غير موجود في النظام.',
        ];
    }
}

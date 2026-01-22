<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiplomaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // تأكد من تفعيلها لكي يسمح لارافل بتنفيذ التحقق
        return true;
    }

    public function rules(): array
    {
        // جلب معرف الدبلوم الحالي من الرابط (Route Parameter) لاستثنائه من فحص الـ Unique
        $diplomaId = $this->route('diploma')?->id;

        return [
            // "sometimes" تعني: إذا تم إرسال الحقل، تحقق منه. إذا لم يُرسل، تجاهله.
            'code' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('diplomas', 'code')->ignore($diplomaId)
            ],

            'institute_id' => 'sometimes|exists:institutes,id',
            'title_ar' => 'sometimes|string|max:150',
            'title_en' => 'nullable|string|max:150',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'total_cost' => 'sometimes|numeric|min:0',

            'image' => 'nullable|image|max:5120',

            'is_open' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',

            // في التحديث، قد لا يرغب المستخدم في تغيير قائمة الكورسات
            'course_ids' => 'sometimes|array|min:1',
            'course_ids.*' => 'exists:courses,id',
        ];
    }
}

<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // أحياناً (sometimes) تعني إذا كان الحقل موجوداً في الطلب فيجب أن يطبق القواعد
            'name_ar'      => 'sometimes|string|max:100',
            'name_en'      => 'nullable|string|max:100',
            'description'  => 'nullable|string',
            'institute_id' => 'sometimes|exists:institutes,id',
            'is_active'    => 'boolean'
        ];
    }
}

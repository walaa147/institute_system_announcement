<?php

namespace App\Http\Requests\Api\Secretary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return Auth::check() && $user->isStatusAdmin();
    }

    public function rules(): array
    {
        // جلب القسم الحالي من الرابط
        $department = $this->route('department');
        // التأكد من الحصول على ID القسم سواء كان كائناً أو رقماً
        $id = is_object($department) ? $department->id : $department;

        // الحصول على ID المعهد: إما من الطلب المرسل أو من بيانات القسم الأصلية في القاعدة
        $instituteId = $this->institute_id ?? (is_object($department) ? $department->institute_id : \App\Models\Department::find($id)?->institute_id);

        return [
            'name_ar' => [
                'sometimes', 'string', 'max:100',
                Rule::unique('departments')
                    ->where(fn($q) => $q->where('institute_id', $instituteId))
                    ->ignore($id)
            ],
            'name_en'        => 'nullable|string|max:100',
            'description_ar' => 'sometimes|string|min:10',
            'description_en' => 'nullable|string|min:10',
            'is_active'      => 'sometimes|boolean'
        ];
    }
}

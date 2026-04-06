<?php

namespace App\Http\Requests\Api\Secretary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return Auth::check() && ($user->isStatusAdmin() || $user->hasRole('super_admin'));
    }

    public function rules(): array
    {
        return [
            'name_ar' => [
                'required', 'string', 'max:100',
                Rule::unique('departments')->where(fn($q) => $q->where('institute_id', $this->institute_id))
            ],
            'name_en'        => 'nullable|string|max:100',
            'institute_id'   => 'sometimes|exists:institutes,id',
            'description_ar' => 'required|string|min:10', // جعلناه مطلوباً لأهمية المحتوى
            'description_en' => 'nullable|string|min:10',
            'is_active'      => 'sometimes|boolean',
        ];
    }
}

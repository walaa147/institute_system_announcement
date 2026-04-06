<?php

namespace App\Http\Requests\Api\Secretary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateAdvertisementRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return Auth::check() && ($user->isStatusAdmin() || $user->isStatusSuperAdmin());
    }

    public function rules(): array
    {
        return [
            'title_ar' => 'sometimes|string|max:150',
            'title_en' => 'nullable|string|max:150',
            'department_id' => 'sometimes|exists:departments,id',

            'advertisable_id' => 'nullable|integer',
            'advertisable_type' => [
                'nullable',
                'string',
                Rule::in(['App\Models\Course', 'App\Models\Diploma'])
            ],

            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'price_after_discount' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'is_open_for_booking' => 'sometimes|boolean',
            // أضف أي حقول أخرى ترى أنها قابلة للتعديل
        ];
    }
}

<?php

namespace App\Http\Requests\Api\Secretary;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\BelongsToInstitute;
use Illuminate\Support\Facades\Auth;


class StoreCourseRequest extends FormRequest
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
            'title_ar'       => ['required', 'string', 'max:255'],
            'title_en'       => ['nullable', 'string', 'max:255'],
            'name_ar'        => ['required', 'string', 'max:255'],
            'name_en'        => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'price'          => ['nullable', 'numeric', 'min:0'],
            'duration'       => ['nullable', 'string', 'max:100'],
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
            'photo'          => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],

            // استخدام القاعدة الشاملة لحماية القسم
            'department_id'  => ['required', 'integer', new BelongsToInstitute('departments')],

            'is_active'      => ['nullable', 'boolean'],
            'is_available'   => ['nullable', 'boolean'],
        ];
    }
}

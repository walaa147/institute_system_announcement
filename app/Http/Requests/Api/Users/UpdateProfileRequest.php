<?php

namespace App\Http\Requests\Api\Users;

use App\Http\Requests\Api\ApiBaseRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiBaseRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $user = $this->user();

        return [
            // حقول جدول Users
            'name'         => 'sometimes|string|max:255',
            'email'        => [
                'sometimes', 'email',
                Rule::unique('users', 'email')->ignore($user->id)
            ],
            'password'     => 'nullable|string|min:8|confirmed',

            // حقول جدول User Profiles بناءً على المخطط
            'full_name_ar' => 'sometimes|string|max:255',
            'full_name_en' => 'sometimes|string|max:255',
            'phone_number' => [
                'sometimes',
                Rule::unique('user_profiles', 'phone_number')->ignore($user->id, 'user_id')
            ],
            'gender'       => 'sometimes|in:male,female',
            'address'      => 'sometimes|string|nullable',
            'city'         => 'sometimes|string|nullable',
            'logo'         => 'sometimes|image|max:2048|nullable',

        ];
    }
}

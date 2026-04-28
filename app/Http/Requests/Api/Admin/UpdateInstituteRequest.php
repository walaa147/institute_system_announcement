<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstituteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $instituteId = $this->route('institute');
         /** @var \App\Models\User $user */
        $user = auth('sanctum')->user();

        // فحص هل المستخدم سوبر أدمن
        $isSuperAdmin = $user && $user->hasRole('super_admin');

        // 1. القواعد المشتركة (يسمح للسكرتير والسوبر أدمن بتعديلها)
        $rules = [
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'address'        => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'website'        => 'nullable|url|max:255',
            'logo'           => 'nullable|image|max:5120',
            'cover_photo'    => 'nullable|image|max:10240',
            'lat'            => ['sometimes', 'numeric', 'between:-90,90'],
            'lng'            => ['sometimes', 'numeric', 'between:-180,180'],
            'email'          => 'nullable|email|max:100|unique:institutes,email,' . $instituteId,
        ];

        // 2. القواعد الخاصة بالسوبر أدمن فقط (حماية مالية وإدارية)
        if ($isSuperAdmin) {
            $rules['name_ar']         = 'sometimes|string|max:255';
            $rules['name_en']         = 'nullable|string|max:255';
            $rules['code']            = 'sometimes|string|unique:institutes,code,' . $instituteId;
            $rules['slug']            = 'sometimes|string|unique:institutes,slug,' . $instituteId;
            $rules['status']          = 'sometimes|boolean';
            $rules['commission_rate'] = ['sometimes', 'numeric', 'min:0', 'max:100'];
            $rules['priority_level']  = ['nullable', 'integer', 'min:0'];
            $rules['points_balance']  = ['nullable', 'integer', 'min:0'];
            $rules['avg_response_time'] = 'nullable|numeric|min:0';
        }

        return $rules;
    }
}

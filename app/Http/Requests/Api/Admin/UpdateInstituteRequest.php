<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstituteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $instituteId = $this->route('institute');
        return [
            'name_ar'     => 'sometimes|string|max:255',
            'name_en'     => 'nullable|string|max:255',
            'code'    => 'sometimes|string|unique:institutes,code,' . $instituteId,
                'slug'    => 'sometimes|string|unique:institutes,slug,' . $instituteId,
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
'status' => 'sometimes|boolean',
            'address'     => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:100|unique:institutes,email,' . $instituteId,
            'logo'       => 'nullable|image|max:5120',
            'cover_photo' => 'nullable|image|max:10240',
            'commission_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'points_balance'  => ['nullable', 'integer', 'min:0'],
            'avg_response_time'=>'nullable|numeric|min:0',
            'lat'             => ['nullable', 'numeric', 'between:-90,90'],
            'lng'             => ['nullable', 'numeric', 'between:-180,180'],
            'priority_level' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

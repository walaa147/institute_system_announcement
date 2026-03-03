<?php

namespace App\Http\Requests;
use App\Http\Requests\Api\ApiBaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreInstituteRequest extends ApiBaseRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name_ar'     => 'required|string|max:255',
            'name_en'     => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'address'     => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:100',
            'logo'       => 'nullable|image|max:5120', // 5MB
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'], // حماية مالية: نسبة من 0 إلى 100
            'points'          => ['nullable', 'integer', 'min:0'],
            'address'         => ['required', 'string', 'max:500'],
            'priority_level' => ['nullable', 'integer', 'min:0'], // حماية من مستويات أولوية سالبة
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstituteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name_ar'     => 'required|string|max:255',
            'name_en'     => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'address'     => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:100',
            'image'       => 'nullable|image|max:5120', // 5MB
        ];
    }
}

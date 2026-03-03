<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Api\ApiBaseRequest;

class StoreInstituteRequest extends ApiBaseRequest
{
     /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
 /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255', 'unique:institutes,name'],
            'address'         => ['required', 'string', 'max:500'],
            'logo'            => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'], // حماية: صورة فقط بحجم أقصى 2 ميجا
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'], // حماية مالية: نسبة من 0 إلى 100
            'points'          => ['nullable', 'integer', 'min:0'],

        ];
    }
}

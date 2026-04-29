<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Api\ApiBaseRequest;
use Illuminate\Validation\Rule;
class StoreSecretaryRequest extends ApiBaseRequest
{
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
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'], // يجب إرسال password_confirmation
            'institute_id' => [
            'required',
            'integer',
            Rule::exists('institutes', 'id')->where(function ($query) {
                // شرط 1: ألا يكون المعهد محذوفاً (هذا يتعامل مع SoftDeletes)
                $query->whereNull('deleted_at')
                // شرط 2: (اختياري) أن يكون المعهد نشطاً
                      ->where('status', true);
            }),
        ],
        ];
    }
}

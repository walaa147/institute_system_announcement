<?php

namespace App\Http\Requests\Api\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JoinWaitlistRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation()
    {
        // تحويل الكلمة البسيطة 'advertisement' إلى المسار الكامل
        $map = [
            'advertisement' => 'App\Models\Advertisement',
        ];

        if ($this->has('bookable_type') && isset($map[strtolower($this->bookable_type)])) {
            $this->merge([
                'bookable_type' => $map[strtolower($this->bookable_type)],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'bookable_id'   => 'required|integer',
            'bookable_type' => [
                'required',
                'string',
                Rule::in(['App\Models\Advertisement']) // التحقق من المسار الكامل بعد التحويل
            ],
        ];
    }
}

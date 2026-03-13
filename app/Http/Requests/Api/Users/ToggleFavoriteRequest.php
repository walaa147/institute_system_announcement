<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class ToggleFavoriteRequest extends FormRequest
{
    /**
     * التحقق من الصلاحية (أن المستخدم مسجل الدخول)
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        return $user->hasRole('student');
    }


    public function rules(): array
    {
        return [

        ];
    }
}

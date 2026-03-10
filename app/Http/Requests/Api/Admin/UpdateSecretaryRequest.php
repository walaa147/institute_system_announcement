<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiBaseRequest;
use Illuminate\Validation\Rule;
class UpdateSecretaryRequest extends ApiBaseRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array

    {
      // الحصول على معرف المستخدم من الرابط (إذا كان موجودًا)
    $parameterName = $this->route()->parameterNames()[0] ?? null;
    $user = $parameterName ? $this->route($parameterName) : null;

    $userId = $user instanceof \App\Models\User ? $user->id : $user;
        return [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                // الطريقة الأكثر أماناً واحترافية في لارافيل
               Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'institute_id' => 'sometimes|integer|exists:institutes,id',
        ];
    }
}

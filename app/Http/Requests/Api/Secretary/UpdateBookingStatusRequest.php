<?php

namespace App\Http\Requests\Api\Secretary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // استخدام Auth::user() مباشرة أضمن
        /** @var \App\Models\User $user */
    $user = Auth::user();

        // التأكد من أن المستخدم مسجل دخول ولديه الصلاحيات المطلوبة
        // ملاحظة: تأكد أن دالة hasRole موجودة في موديل User الخاص بك (عبر حزمة Spatie أو Custom)
        return $user && ($user->hasRole('secretary') || $user->hasRole('admin'));
    }

    public function rules(): array
    {
        return [
            // القواعد الأساسية لتحديث الحالة
            'status' => 'required|string|in:confirmed,cancelled,attended',
            'admin_notes' => 'nullable|string|max:500',
        ];
    }

    protected function prepareForValidation()
    {
        // تحويل الحالة إلى حروف صغيرة لتجنب أخطاء الإدخال (Confirmed -> confirmed)
        if ($this->has('status')) {
            $this->merge([
                'status' => strtolower($this->status),
            ]);
        }
    }
}

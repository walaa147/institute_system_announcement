<?php

namespace App\Http\Requests\Api\Secretary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Rules\BelongsToInstitute;

class UpdateCourseRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرحاً له بعمل هذا الطلب
     */
    public function authorize(): bool
    {
        // السماح للسكرتارية والأدمن والسوبر أدمن
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return Auth::check() && ($user->isStatusAdmin() || $user->isStatusSuperAdmin() || $user->hasRole('secretary'));
    }

    /**
     * قواعد التحقق
     */
    public function rules(): array
    {
        return [
            // استخدمنا sometimes لكي يتم فحص الحقل فقط إذا تم إرساله في الطلب
            'title_ar'       => ['sometimes', 'required', 'string', 'max:255'],
            'title_en'       => ['nullable', 'string', 'max:255'],
            'name_ar'        => ['sometimes', 'required', 'string', 'max:255'],
            'name_en'        => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'price'          => ['nullable', 'numeric', 'min:0'],
            'duration'       => ['nullable', 'string', 'max:100'],
            'start_date'     => ['nullable', 'date'],
            // التأكد أن تاريخ الانتهاء بعد أو يساوي تاريخ البدء (إذا تم إرسالهما)
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
            // التحقق من الصورة في حال تم رفع صورة جديدة
            'photo_path'     => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],

            // استخدام القاعدة الشاملة لحماية القسم (فقط إذا قرر السكرتير تغيير القسم)
            'department_id'  => ['sometimes', 'required', 'integer', new BelongsToInstitute('departments')],

            'is_active'      => ['nullable', 'boolean'],
            'is_available'   => ['nullable', 'boolean'],
        ];
    }
}

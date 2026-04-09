<?php

namespace App\Http\Requests\Api\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // تأكد أن المستخدم مسجل دخول لعمل حجز
        return Auth::check();
    }

    protected function prepareForValidation()
    {
        // ندمج معرف المستخدم في البيانات قبل البدء بالفحص (Validation)
        $this->merge([
            'user_id' => Auth::id(),
        ]);
    }

    public function rules(): array
    {
        return [
            'bookable_type' => ['required', 'string', 'in:App\Models\Advertisement,App\Models\Course,App\Models\Diploma'],

            'bookable_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $model = $this->bookable_type;
                    // فحص إذا كان الموديل موجود فعلاً في قاعدة البيانات
                    if (!class_exists($model) || !$model::where('id', $value)->exists()) {
                        $fail('العنصر المطلوب حجزه غير موجود.');
                    }
                },
            ],

            'user_id' => [
                'required',
                Rule::unique('bookings')->where(function ($query) {
                    return $query->where('user_id', Auth::id())
                                 ->where('bookable_id', $this->bookable_id)
                                 ->where('bookable_type', $this->bookable_type);
                })
            ]
        ];
    }


}

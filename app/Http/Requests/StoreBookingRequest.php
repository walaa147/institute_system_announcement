<?php

namespace App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Course;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
{
    return [
        // كورس_id مطلوب فقط إذا لم يتم إرسال diploma_id
        'course_id' => [
            'required_without:diploma_id',
            'nullable',
            'exists:courses,id',
            'bail',
            function ($attribute, $value, $fail) {
                if ($value) {
                    $course = \App\Models\Course::find($value);
                    if (!$course || (int)$course->is_open !== 1) {
                        $fail('الكورس المحدد مغلق حالياً ولا يمكن حجزه.');
                    }
                }
            },
        ],

        // diploma_id مطلوب فقط إذا لم يتم إرسال course_id
        'diploma_id' => [
            'required_without:course_id',
            'nullable',
            'exists:diplomas,id',
            'bail',
            function ($attribute, $value, $fail) {
                if ($value) {
                    $diploma = \App\Models\Diploma::find($value);
                    // ملاحظة: تأكد أن جدول الدبلومات يحتوي على حقل is_open أيضاً
                    if (!$diploma || (int)$diploma->is_open !== 1) {
                        $fail('الدبلوم المحدد مغلق حالياً ولا يمكن حجزه.');
                    }
                }
            },
        ],
    ];
}

// إضافة رسائل خطأ مخصصة لجعلها أكثر وضوحاً
public function messages(): array
{
    return [
        'course_id.required_without' => 'يجب اختيار كورس أو دبلوم لإتمام عملية الحجز.',
        'diploma_id.required_without' => 'يجب اختيار كورس أو دبلوم لإتمام عملية الحجز.',
    ];
}
}

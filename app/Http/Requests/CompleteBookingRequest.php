<?php

namespace App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Booking;
use Illuminate\Support\Facades\Gate; // استدعاء Gate

class CompleteBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // استخدام Gate::allows هو الحل الأضمن لتجنب أخطاء التعريف
        return Auth::check() && Gate::allows('course.manage');
    }

    public function rules(): array
    {
        return [
            'booking_id' => [
                'required',
                'exists:bookings,id',
                'bail',
                function ($attribute, $value, $fail) {
                    $booking = Booking::find($value);
                    if ($booking && $booking->is_paid) {
                        $fail('عملية التسجيل هذه مدفوعة ومكتملة بالفعل.');
                    }
                }
            ],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}

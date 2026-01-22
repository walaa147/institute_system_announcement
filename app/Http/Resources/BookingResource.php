<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * تحويل الكائن إلى مصفوفة JSON.
     */
    public function toArray($request): array
{
    return [
        'id'           => $this->id,
        'booking_type' => $this->diploma_id ? 'diploma' : 'course',
        'customer'     => [
            'id'    => $this->user_id,
            'name'  => $this->whenLoaded('user', fn() => $this->user->name),
        ],
        'booked_item' => [
    'id'    => $this->diploma_id ?? $this->course_id,
    'title' => $this->diploma_id
        ? $this->whenLoaded('diploma', fn() => $this->diploma->title_ar) // الدبلوم يستخدم title_ar
        : $this->whenLoaded('course', fn() => $this->course->name_ar),    // الكورس يستخدم name
    'type'  => $this->diploma_id ? 'Diploma' : 'Course',
],
        'pricing'      => [
            'original_price' => (float) $this->original_price,
            'final_price'    => (float) $this->final_price,
        ],
        'is_paid'      => (bool) $this->is_paid,
        'created_at'   => $this->created_at->format('Y-m-d H:i'),
    ];
}
}



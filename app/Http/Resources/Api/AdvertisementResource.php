<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'slug'              => $this->slug,
            'title_ar'          => $this->title_ar,
            'title_en'          => $this->title_en,
            'description_ar'    => $this->description_ar,
            'description_en'    => $this->description_en,
            'image_url'         => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'trainer_name'      => $this->trainer_name,
            'location'          => $this->location,
            'link'              => $this->link,

            // الأسعار والحالة
            'is_active'           => (bool) $this->is_active,
            'is_free'             => (bool) $this->is_free,
            'price_before'        => $this->price_before_discount,
            'price_after'         => $this->price_after_discount,
            'discount_percentage' => $this->discount_percentage,

            // المقاعد والحجز
            'max_seats'           => $this->max_seats,
            'available_seats'     => $this->max_seats - $this->current_seats_taken,
            'is_open_for_booking' => (bool) $this->is_open_for_booking,

            // التواريخ
            'event_date'          => $this->event_date?->format('Y-m-d H:i'),
            'expired_at'          => $this->expired_at?->format('Y-m-d H:i'),

            // العلاقات (Eager Loading)
            'institute'           => $this->whenLoaded('institute'),
            'department'          => $this->whenLoaded('department'),

            // المنطق الخاص بالربط المتعدد (Course or Diploma)
            'related_info'        => $this->when($this->advertisable_type, function() {
                return [
                    'type' => str_replace('App\\Models\\', '', $this->advertisable_type),
                    'data' => $this->advertisable, // سيعيد بيانات الكورس أو الدبلوم كاملة
                ];
            }),

            // الإحصائيات
            'bookings_count'      => $this->whenCounted('bookings'),
            'waiting_list_count'  => $this->whenCounted('waitingLists'),
        ];
    }
}

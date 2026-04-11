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
            'has_certificate'     => (bool) $this->has_certificate,
            'price_before'        => $this->price_before_discount,
            'price_after'         => $this->price_after_discount,
            'discount_percentage' => $this->discount_percentage,
            // أضف هذه الأسطر داخل مصفوفة الـ toArray
'early_paid_price'       => $this->early_paid_price,
'early_paid_seats_limit' => (int) $this->early_paid_seats_limit,
'discount_expiry'        => $this->discount_expiry?->format('Y-m-d H:i'),
'max_seats'           => (int)$this->max_seats,
'current_seats_taken' => (int)$this->current_seats_taken,
'available_seats'     =>max(0, $this->max_seats - $this->current_seats_taken),

// هل الحجز المبكر لا يزال متاحاً؟
'is_early_bird_available' => $this->early_paid_seats_limit > 0 && ($this->current_seats_taken < $this->early_paid_seats_limit),
// حالة المقاعد
'is_full' => $this->current_seats_taken >= $this->max_seats,
'is_open_for_booking' => (bool) $this->is_open_for_booking,


            // التواريخ
            'event_date'          => $this->event_date?->format('Y-m-d H:i'),
            'expired_at'          => $this->expired_at?->format('Y-m-d H:i'),
            'start_date'   => $this->start_date?->format('Y-m-d H:i'),
'end_date'     => $this->end_date?->format('Y-m-d H:i'),
'published_at' => $this->published_at?->format('Y-m-d H:i'),
            'duration'     => $this->duration,

            // العلاقات
            'institute'          => $this->whenLoaded('institute', function() {
                return [
                    'id' => $this->institute->id,
                    'name_ar' => $this->institute->name_ar,
                    'name_en' => $this->institute->name_en,
                ];
            }),
            'department'         => $this->whenLoaded('department', function() {
                return [
                    'id' => $this->department?->id,
                    'name_ar' => $this->department?->name_ar,
                    'name_en' => $this->department?->name_en,
                ];
            }),
'created_by'         => $this->whenLoaded('creator', function() {
    return [
        'id' => $this->creator->id,
        'name' => $this->creator->name,
    ];
}),
'updated_by'         => $this->whenLoaded('updater', function() {
    return [
        'id' => $this->updater?->id,
        'name' => $this->updater?->name,
    ];
}),


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

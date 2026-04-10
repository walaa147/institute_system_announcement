<?php

namespace App\Http\Resources\Api; // تأكد أن حرف R و A كبيران

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'status'         => $this->status, // draft, confirmed, cancelled, attended
            'booking_type'   => $this->booking_type, // early (مبكر) أو regular (عادي)

            // بيانات الطالب
            'customer'       => [
                'id'   => $this->user_id,
                'name' => $this->whenLoaded('user', fn() => $this->user->name),
            ],

            // بيانات الإعلان المحجوز
            'booked_item'    => [
                'id'    => $this->bookable_id,
                'type'  => str_replace('App\Models\\', '', $this->bookable_type),
                'title' => $this->getBookableTitle(),
            ],

            // تفاصيل السعر
            'pricing'        => [
                'original_price'  => (float) $this->original_price,
                'discount_amount' => (float) $this->discount_amount,
                'final_price'     => (float) $this->final_price,
            ],

            // تفاصيل الدفع والبنك (مهمة للموبايل وللسكرتير)
            'payment_info'   => [
                'payment_status'  => $this->payment_status, // pending, authorized, paid
                'is_paid'         => (bool) $this->is_paid,
                'method'          => $this->payment_method,
                'transaction_id'  => $this->transaction_id,
                'paid_at'         => $this->paid_at ? $this->paid_at->format('Y-m-d H:i') : null,
            ],

            // بيانات الأداء والوقت
            'performance'    => [
                'created_at'   => $this->created_at->format('Y-m-d H:i'),
                'confirmed_at' => $this->confirmed_at ? $this->confirmed_at->format('Y-m-d H:i') : null,
                'processed_by' => $this->whenLoaded('processor', fn() => $this->processor->name),
            ],

            'can_review'     => $this->status === 'attended',
        ];
    }

    /**
     * دالة مساعدة لجلب العنوان بناءً على نوع الموديل المحجوز
     */
    protected function getBookableTitle()
    {
        if (!$this->relationLoaded('bookable')) {
            return null;
        }

        // حسب جدول الإعلانات الخاص بك، نستخدم title_ar أو title
        return match ($this->bookable_type) {
            'App\Models\Advertisement' => $this->bookable->title_ar ?? $this->bookable->title,
            'App\Models\Course'        => $this->bookable->name_ar ?? $this->bookable->name,
            'App\Models\Diploma'       => $this->bookable->title_ar ?? $this->bookable->title,
            default                    => 'Unknown Item',
        };
    }
}

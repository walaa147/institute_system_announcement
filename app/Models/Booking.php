<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'bookings';

    protected $fillable = [
        'user_id',
        'bookable_id',
        'bookable_type',
        'original_price',
        'discount_amount',
        'final_price',
        'status',          // (draft, confirmed, cancelled, attended)
        'confirmed_at',    // وقت موافقة السكرتير
        'processed_by',    // السكرتير المسؤول
        'is_paid',
        'payment_details',
        'booking_date',
        'payment_method',
        'transaction_id',
        'paid_at',
        'payment_status', // (pending, authorized, paid)
        'booking_type',   // (early, regular)
        'bank_payment_id', // ID الدفع البنكي (للموبايل)
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'payment_details' => 'array',
        'confirmed_at' => 'datetime',
        'booking_date' => 'datetime',
        'original_price' => 'float',
        'discount_amount' => 'float',
        'final_price' => 'float',
    ];

    // الطالب الذي قام بالحجز
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // العلاقة المتعددة (كورس، دبلوم، إعلان)
    public function bookable()
    {
        return $this->morphTo();
    }

    // الوصول للمعهد عبر الإعلان (إذا كان المحجوز إعلاناً)
    public function institute()
    {
        return $this->hasOneThrough(
            Institute::class,
            Advertisement::class,
            'id',           // ID في جدول الإعلانات
            'id',           // ID في جدول المعاهد
            'bookable_id',  // الحقل في جدول الحجوزات
            'institute_id'  // الحقل في جدول الإعلانات
        );
    }

    // السكرتير الذي عالج الطلب
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
    protected static function booted()
{
    static::created(function ($booking) {
        // التأكد أن الحجز يخص إعلان
        if ($booking->bookable_type === 'App\Models\Advertisement' || str_contains($booking->bookable_type, 'Advertisement')) {

            // جلب المسار الصحيح للموديل كما هو مخزن في الداتابيز
            $preciseType = (new \App\Models\Advertisement())->getMorphClass();

            // التحديث المباشر
            \Illuminate\Support\Facades\DB::table('waiting_lists')
                ->where('user_id', $booking->user_id)
                ->where('bookable_id', $booking->bookable_id)
                ->where('bookable_type', $preciseType)
                ->where('status', 'notified')
                ->update([
                    'status' => 'converted',
                    'updated_at' => now()
                ]);
        }
    });

}
}

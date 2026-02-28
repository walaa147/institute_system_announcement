<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    // تحديد اسم الجدول بعد إعادة التسمية
    protected $table = 'bookings';

    protected $fillable = [
        'user_id',
        'course_id',
        'diploma_id',
        'original_price',
        'discount_amount',
        'final_price',
        'is_paid',
        'payment_details',
        'enrollment_date',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'payment_details' => 'array',
        'booking_date' => 'datetime',
        'original_price' => 'float',
        'discount_amount' => 'float',
        'final_price' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    public function diploma()
{
    return $this->belongsTo(Diploma::class);
}

public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }
    public function bookable()
{
    return $this->morphTo();
}
}

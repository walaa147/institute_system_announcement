<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advertisement extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    // العلاقة التي تجلب الدورة أو الدبلوم المرتبط
   public function advertisable()
{
    return $this->morphTo();
}

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

   public function bookings()
{
    return $this->morphMany(Booking::class, 'bookable');
}

public function waitingLists()
{
    return $this->morphMany(WaitingList::class, 'bookable');
}
}

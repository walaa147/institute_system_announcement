<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Advertisement extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $casts = [
        'is_active' => 'boolean',
        'is_open_for_booking' => 'boolean',
        'is_free' => 'boolean',
        'has_certificate' => 'boolean',
        'event_date' => 'datetime',
        'discount_expiry' => 'datetime',
    ];

     protected $with = ['institute:id,name_ar,name_en','department:id,name_ar,name_en'];
     protected $withCount = ['bookings','waitingLists']; // لجلب عدد الحجوزات وقوائم الانتظار تلقائياً

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
// protected static function booted()
// {
//     static::addGlobalScope('active', function (Builder $builder) {
//         // نحدد المستخدم عبر sanctum لضمان الدقة في الـ API
//         $user = auth('sanctum')->user();

//         // نطبق الفلترة فقط إذا:
//         // 1. المستخدم زائر (غير مسجل).
//         // 2. أو المستخدم مسجل لكنه ليس (Admin/Secretary).
//         if (!$user || !($user instanceof \App\Models\User && $user->isStatusAdmin())) {
//             $builder->where('is_active', true);
//         }
//         else{
//            $builder->where('institute_id', $user->institute_id);

//         }
//     });
// }
protected static function booted()
{
    static::addGlobalScope('active_access', function (Builder $builder) {
        $user = auth('sanctum')->user();

        // 1. إذا كان زائر أو مستخدم عادي (ليس أدمن/سكرتير) -> يرى المفعل فقط
        if (!$user || !($user instanceof \App\Models\User && $user->isStatusAdmin())) {
            $builder->where('is_active', true);
            return;
        }

        // 2. إذا كان "سوبر أدمن" -> يرى كل شيء في كل المعاهد
        if ($user->hasRole('super_admin')) {
            return;
        }

        // 3. المنطق المشترك للسكرتير/أدمن المعهد:
        // يرى (كل بيانات معهده) أَوْ (البيانات المفعلة في المعاهد الأخرى)
        $builder->where(function ($query) use ($user) {
            $query->where('institute_id', $user->institute_id)
                  ->orWhere('is_active', true);
        });
    });
}
}

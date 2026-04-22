<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Advertisement extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active'           => 'boolean',
        'is_open_for_booking' => 'boolean',
        'is_free'             => 'boolean',
        'has_certificate'     => 'boolean',
        'published_at'        => 'datetime',
        'start_date'          => 'datetime',
        'end_date'            => 'datetime',
        'event_date'          => 'datetime',
        'discount_expiry'     => 'datetime',
        'expired_at'          => 'datetime',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    protected $with = ['institute:id,name_ar,name_en', 'department:id,name_ar,name_en'];
    protected $withCount = ['bookings', 'waitingLists'];

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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function booted()
    {
        static::addGlobalScope('active_access', function (Builder $builder) {
            /** @var \App\Models\User|null $user */
            $user = auth('sanctum')->user();
            $now = Carbon::now();

            // 1. إذا كان زائر أو مستخدم عادي (ليس أدمن/سكرتير) -> يرى المفعل فقط
            if (!$user || !($user instanceof \App\Models\User && $user->isStatusAdmin())) {
                $builder->where('is_active', true)
                    ->whereHas('institute', function ($query) {
                        $query->where('status', true);
                    })
                    ->where(function ($query) use ($now) {
                        $query->whereNull('published_at')->orWhere('published_at', '<=', $now);
                    })
                    ->where(function ($query) use ($now) {
                        $query->whereNull('expired_at')->orWhere('expired_at', '>=', $now);
                    });
                return;
            }

            // 2. إذا كان "سوبر أدمن" -> يرى كل شيء في كل المعاهد
            if ($user->hasRole('super_admin')) {
                return;
            }

            // 3. المنطق المشترك للسكرتير/أدمن المعهد:
            // يرى (كل بيانات معهده) أَوْ (البيانات المفعلة في المعاهد الأخرى)
            $builder->where(function ($query) use ($user, $now) {
                $query->where('institute_id', $user->institute_id)
                    ->orWhere(function ($q) use ($now) {
                        $q->where('is_active', true)
                            ->whereHas('institute', function ($instQ) {
                                $instQ->where('status', true);
                            })
                            ->where(function ($subQ) use ($now) {
                                $subQ->whereNull('published_at')->orWhere('published_at', '<=', $now);
                            })
                            ->where(function ($subQ) use ($now) {
                                $subQ->whereNull('expired_at')->orWhere('expired_at', '>=', $now);
                            });
                    });
            });
        });
    }
}

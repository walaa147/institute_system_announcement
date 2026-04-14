<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
class Diploma extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'total_cost',
        'photo_path',
        'is_active',
        'is_open',
        'institute_id',
        'created_by',
        'updated_by',
    ];

    /**
     * العلاقة مع الكورسات (Many-to-Many)
     * الدبلوم يحتوي على عدة كورسات
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_diploma')
                    ->withPivot('sort_order') // لجلب ترتيب الكورس داخل الدبلوم
                    ->withTimestamps();
    }

    /**
     * العلاقة مع المعهد
     */
    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    /**
     * علاقة الموظف المنشئ
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    /**
     * علاقة الموظف المعدل
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'updated_by');
    }
    public function likes(): \Illuminate\Database\Eloquent\Relations\MorphMany
{
    // ربطها بالموديل الجديد واستخدام العلاقة "likeable"
    return $this->morphMany(Like::class, 'likeable');
}


public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function bookings()
{
    return $this->morphMany(Booking::class, 'bookable');
}

public function waitingLists() 
{
    return $this->morphMany(WaitingList::class, 'bookable');
}
public function advertisements(): MorphMany
    {
        return $this->morphMany(Advertisement::class, 'advertisable');
    }
    protected static function booted()// هذا هو المكان المناسب لوضع اللوجيك الذي يتعامل مع تحديث الإعلانات المرتبطة بالدبلوم
    {
        static::updated(function ($diploma) {
            // الحقول التي إذا تغيرت يجب تحديث الإعلان المرتبط بها
            $relevantFields = ['name_ar', 'description_ar', 'total_cost', 'photo_path', 'duration'];

            if ($diploma->wasChanged($relevantFields)) {
                // تحديث كل الإعلانات المرتبطة بهذا الدبلوم
                $diploma->advertisements()->update([
                    'title_ar'              => $diploma->name_ar,
                    'description_ar'        => $diploma->description_ar,
                    'price_before_discount' => $diploma->total_cost, // ربط التكلفة بسعر الإعلان
                    'image_path'            => $diploma->photo_path,
                    'duration'              => $diploma->duration,
                ]);
            }
        });
    }
}

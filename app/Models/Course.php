<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class Course extends Model
{
    use HasFactory;

    // الحقول التي يمكن تعبئتها من Postman أو تطبيق الفلاتر
    protected $fillable = [
        'code',
        'title_ar',
        'title_en',
        'name_ar',
        'name_en',
        'description',
        'price',
        'photo_path',
        'department_id',
        'is_open',
        'is_active',
        'created_by',
    'updated_by',
    'slug',
    'duration',
    'start_date',
    'end_date',
    'institute_id',
    ];

    /**
     * العلاقة مع القسم: الدورة تتبع قسماً واحداً
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * سكووب (Scope) لجلب الدورات النشطة فقط للفلاتر
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * الوصول السهل لرابط الصورة الكامل لمبرمج الفلاتر
     */
    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }
    // داخل كلاس Course
public function creator()
{
    // الكورس ينتمي إلى موظف (Employee) من خلال حقل created_by
    return $this->belongsTo(User::class, 'created_by');
}
public function updater()
{
    return $this->belongsTo(User::class, 'updated_by');
}
// أضف هذه الدالة داخل كلاس Course
public function diplomas(): BelongsToMany
{
    return $this->belongsToMany(Diploma::class, 'course_diploma')
                ->withPivot('sort_order')
                ->withTimestamps();
}
public function likes(): \Illuminate\Database\Eloquent\Relations\MorphMany
{
    // ربطها بالموديل الجديد واستخدام العلاقة "likeable"
    return $this->morphMany(Like::class, 'likeable');
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

public function advertisements()
{
    return $this->morphMany(Advertisement::class, 'advertisable');
}


protected static function booted()// هذا هو المكان المناسب لوضع اللوجيك الذي يتعامل مع تحديث الإعلانات المرتبطة بالكورس
{
    static::updated(function ($course) {
        // نتحقق من الحقول التي تغيرت في الكورس
        $relevantFields = ['name_ar', 'description', 'price', 'photo_path', 'duration'];

        if ($course->wasChanged($relevantFields)) {
            // استخدام العلاقة مباشرة بدلاً من الاستعلام اليدوي
            $course->advertisements()->update([
                'title_ar'              => $course->name_ar,
                'description_ar'        => $course->description,
                'price_before_discount' => $course->price,
                'image_path'            => $course->photo_path,
                'duration'              => $course->duration,
            ]);
        }
    });
}

}


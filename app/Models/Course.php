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
    return $this->belongsTo(Employee::class, 'created_by');
}
public function updater()
{
    return $this->belongsTo(Employee::class, 'updated_by');
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
}


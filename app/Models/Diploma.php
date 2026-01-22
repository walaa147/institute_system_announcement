<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Diploma extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title_ar',
        'title_en',
        'description_ar',
        'description_ar',
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
}

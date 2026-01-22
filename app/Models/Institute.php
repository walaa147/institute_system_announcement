<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institute extends Model
{
    use HasFactory;

    // الحقول القابلة للتعبئة (Mass Assignment)
    protected $fillable = [
        'name_ar',
        'name_en',
        'description',
        'address',
        'photo_path',
        'phone',
        'email',
    ];

    /**
     * علاقة المعهد بالموظفين
     * المعهد الواحد لديه العديد من الموظفين
     */
     public function employees()
     {
        return $this->hasMany(Employee::class);
     }

    /**
     * علاقة المعهد بالدورات
     * المعهد الواحد ينشر العديد من الدورات
     */
   public function departments()
{
    return $this->hasMany(Department::class);
}

// الوصول للكورسات عبر الأقسام
public function courses()
{
    return $this->hasManyThrough(Course::class, Department::class);
}
}

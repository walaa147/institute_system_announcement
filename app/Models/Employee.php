<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'code', 'user_id', 'institute_id', 'name_ar', 'name_en',
        'gender', 'phone', 'address', 'job_title', 'hire_date',
        'salary', 'is_active'
    ];

    // علاقة الموظف بحسابه كمستخدم (للدخول)
    public function user() {
        return $this->belongsTo(User::class);
    }

    // علاقة الموظف بالمعهد الذي يعمل فيه
    public function institute() {
        return $this->belongsTo(Institute::class);
    }
    /**
 * جلب جميع الدبلومات التي قام هذا الموظف بإنشائها
 */
public function createdDiplomas(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Diploma::class, 'created_by');
}

/**
 * جلب جميع الدبلومات التي قام هذا الموظف بتعديلها
 */
public function updatedDiplomas(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Diploma::class, 'updated_by');
}
}

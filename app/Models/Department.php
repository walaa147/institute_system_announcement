<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model

{
    protected $fillable = ['name_ar', 'name_en', 'description', 'institute_id', 'is_active'];

    // العلاقة العكسية: القسم يتبع معهد واحد
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    // القسم يحتوي على العديد من الدورات (سننشئها لاحقاً)
    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
    //


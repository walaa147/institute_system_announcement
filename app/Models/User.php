<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // موجودة مسبقاً
use Spatie\Permission\Traits\HasRoles; // أضف هذا السطر للصلاحيات

class User extends Authenticatable
{
    // أضف HasApiTokens و HasRoles هنا داخل سطر الـ use
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',

        'institute_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function employeeProfile() {
    return $this->hasOne(Employee::class);
}
public function likes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        // الربط المباشر بجدول Favorites عبر user_id
        return $this->hasMany(Like::class);
    }
    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class);
    }
    public function hasPurchasedCourse($courseId): bool
    {
        return $this->bookings()
            ->where('course_id', $courseId)
            ->where('is_paid', true)
            ->exists();
    }



    // علاقة المستخدم بالمعهد (للسكرتير)
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    // علاقة المستخدم بالملف الشخصي
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function favorites()
{
    return $this->hasMany(Favorite::class);
}

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


use Illuminate\Database\Eloquent\SoftDeletes;

class Institute extends Model
{
    use HasFactory;
    use SoftDeletes;


    // الحقول القابلة للتعبئة (Mass Assignment)
   /*protected $fillable = [
        'name_ar',
        'name_en',
        'description',
        'address',
        'photo_path',
        'phone',
        'email',
        'website',
        'lat',
        'lng',
        'commission_rate',
        'priority_level',
        'points_balance',
        'status',
        'slug',
        'code',
        'logo',
        'cover_photo',
        'avg_response_time'

    ];*/
    protected $guarded = ['id'];

    //حساب الأولوية الذكية: أولاً حسب مستوى الأولوية، ثم حسب رصيد النقاط، وأخيراً حسب زمن الاستجابة
    public function scopeOrderBySmartPriority($query)
            {
           return $query->orderByDesc('priority_level')->latest();//
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_photo ? asset('storage/' . $this->cover_photo) : null;
    }
    //********************************************************** */
    public function scopeWithDistance($query, $userLat, $userLng)// لحساب المسافة بين المستخدم والمعهد وإضافتها كحقل افتراضي في الاستعلام
{
    if (!$userLat || !$userLng) {
        return $query->select('*')->selectRaw("NULL AS distance");
    }

    // معادلة Haversine لحساب المسافة الجغرافية
    $rawDistance = "(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat))))";

    return $query->select('*')
        ->selectRaw("{$rawDistance} AS distance", [$userLat, $userLng, $userLat]);

}
public function scopeActive($query)
{
    return $query->where('status', 1);
}
//********************************************************** */
    // العلاقات
   public function departments()// الأقسام التابعة للمعهد
{
    return $this->hasMany(Department::class);
}

public function courses()// الكورسات التابعة للمعهد
    {
        return $this->hasMany(Course::class, 'institute_id');
    }
public function diplomas()// الدبلومات التابعة للمعهد
    {
        return $this->hasMany(Diploma::class, 'institute_id');

    }
    public function advertisements()// الإعلانات التابعة للمعهد
    {
        return $this->hasMany(Advertisement::class, 'institute_id');
        }


public function users()// المستخدمون التابعين للمعهد
    {
        return $this->hasMany(User::class);
    }



public function favorites()
{
    return $this->hasMany(Favorite::class);
}
protected $casts = [
    'status' => 'boolean', // سيحول الـ 1 إلى true والـ 0 إلى false تلقائياً
];

}

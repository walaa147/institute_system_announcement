<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Facades\Auth;

class Department extends Model
{

// لجلب اسم المعهد دائماً مع القسم (اختياري حسب الحاجة)
protected $with = ['institute:id,name_ar,name_en'];
protected $withCount =['courses','diplomas','advertisements']; // لجلب عدد الكورسات والدبلومات والإعلانات تلقائياً
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'institute_id',
        'is_active'
    ];
    // تطبيق فلتر الحالة النشط/غير النشط تلقائياً لجميع الاستعلامات
   protected static function booted()
{
    static::addGlobalScope('active', function (Builder $builder) {
        // نحدد المستخدم عبر sanctum لضمان الدقة في الـ API
        $user = auth('sanctum')->user();

        // نطبق الفلترة فقط إذا:
        // 1. المستخدم زائر (غير مسجل).
        // 2. أو المستخدم مسجل لكنه ليس (Admin/Secretary).
        if (!$user || !($user instanceof \App\Models\User && $user->isStatusAdmin())) {
            $builder->where('is_active', true);
        }
        else{
           $builder->where('institute_id', $user->institute_id);

        }
    });
}

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
    public function diplomas()
    {
        return $this->hasMany(Diploma::class)->where('is_active', true);
    }
    public function advertisements()
    {
        return $this->hasMany(Advertisement::class)->where('is_active', true);
    }
}

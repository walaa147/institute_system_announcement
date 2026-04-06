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
    static::addGlobalScope('active_access', function (Builder $builder) {
        $user = auth('sanctum')->user();

        // 1. إذا كان زائر أو مستخدم عادي (ليس أدمن/سكرتير) -> يرى المفعل فقط
        if (!$user || !($user instanceof \App\Models\User && $user->isStatusAdmin())) {
            $builder->where('is_active', true);
            return;
        }

        // 2. إذا كان "سوبر أدمن" -> يرى كل شيء في كل المعاهد
        if ($user->hasRole('super_admin')) {
            return;
        }

        // 3. المنطق المشترك للسكرتير/أدمن المعهد:
        // يرى (كل بيانات معهده) أَوْ (البيانات المفعلة في المعاهد الأخرى)
        $builder->where(function ($query) use ($user) {
            $query->where('institute_id', $user->institute_id)
                  ->orWhere('is_active', true);
        });
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

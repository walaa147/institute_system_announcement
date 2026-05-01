<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Facades\Auth;

class Department extends Model
{

// لجلب اسم المعهد دائماً مع القسم (اختياري حسب الحاجة)
protected $with = ['institute:id,name_ar,name_en,status']; // جلب اسم المعهد فقط لتقليل حجم البيانات
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
    protected static function booted()
{
    static::addGlobalScope('active_access', function (Builder $builder) {
        /** @var \App\Models\User|null $user */
        $user = auth('sanctum')->user();

        // 1. سوبر أدمن يرى كل شيء
        if ($user && $user->hasRole('super_admin')) {
            return;
        }

        // 2. زائر أو مستخدم عادي (ليس أدمن/سكرتير): يرى النشط فقط من معهد نشط
        if (!$user || !($user instanceof \App\Models\User && $user->isStatusAdmin())) {
            $builder->where('is_active', true)
                    ->whereHas('institute', fn($q) => $q->where('status', true));
            return;
        }

        // 3. سكرتير/أدمن المعهد: يرى بيانات معهده + البيانات النشطة في المعاهد الأخرى
        $builder->where(function ($query) use ($user) {
            $query->where('institute_id', $user->institute_id) // يرى كل أقسام معهده
                  ->orWhere(function ($q) { // أو يرى أقسام المعاهد الأخرى بشرط نشاطها ونشاط المعهد
                        $q->where('is_active', true)
                          ->whereHas('institute', fn($instQ) => $instQ->where('status', true));
                  });
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

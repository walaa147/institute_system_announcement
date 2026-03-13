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
            /** @var User $user */
            $user = Auth::user();

            // إذا لم يكن المستخدم مسجل دخول، أو كان طالباً (ليس أدمن ولا سكرتير)
            if (!$user || !$user->isStatusAdmin()) {
                $builder->where('is_active', true);
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

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
Use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Institute;

class InstituteResource extends JsonResource
{

    public function toArray($request): array
    {$user = $request->user('sanctum') ?? auth('sanctum')->user();

    // فحص الصلاحية عبر السيرفس الخاص بالصلاحيات لضمان عدم وجود تلاعب
    $isSuperAdmin = false;
    if ($user) {
        // استخدم الخيار الأكثر صرامة
        $isSuperAdmin = $user->hasRole('super_admin');
    }
    return [

            'id'          => $this->id,
            'code'        => $this->code,
            'name_ar'     => $this->name_ar,
            'name_en'     => $this->name_en,
            'slug'        => $this->slug,
            'status'      => $this->status,

            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            // معلومات الاتصال
            'address'     => $this->address,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'website'     => $this->website,
            // 'is_favorite' => $this->when($user, function () use ($user) {
            //     // تحقق إذا كان المستخدم قد أضاف هذا المعهد إلى المفضلة
            //     return $user->favoriteInstitutes()->where('institute_id', $this->id)->exists();
            // }),
            // روابط الصور (توليد URL كامل إذا كانت الصورة موجودة)
             'logo_url'    => $this->logo ? url('storage/' . $this->logo) : null,
             'cover_photo_url' => $this->cover_photo ? url('storage/' . $this->cover_photo) : null,
          // الموقع الجغرافي والمسافة
            'location' => [
                'lat'      => (float) $this->lat,
                'lng'      => (float) $this->lng,
                // ستظهر المسافة فقط إذا تم حسابها في الكنترولر عبر withDistance
                'distance' => $this->when(isset($this->distance), function () {
                    return round($this->distance, 2) . ' كم';
                }),
            ],
            // إحصائيات سريعة (تظهر فقط إذا تم استدعاء withCount في الكنترولر)
            'stats' => [
                'departments_count' => $this->whenCounted('departments'),
                'courses_count'     => $this->whenCounted('courses'),
                'diplomas_count'    => $this->whenCounted('diplomas'),
                'ads_count'         => $this->whenCounted('advertisements'),
            ],

$this->mergeWhen($isSuperAdmin, [
        'admin_settings' => [ // وضعهم في مصفوفة فرعية أفضل للتنظيم
            'commission_rate'   => (float) $this->commission_rate,
            'points_balance'    => (int) $this->points_balance,
            'priority_level'    => (int) $this->priority_level,
            'avg_response_time' => (float) $this->avg_response_time,
        ]
    ]),

            'created_at'  => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CourseResource extends JsonResource
{
    public function toArray($request): array
    {
        // جلب المستخدم الحالي مع تحديد النوع لضمان راحة المحرر
        /** @var \App\Models\User|null $user */
        $user = Auth('sanctum')->user();

        return [
            // 1. المعرفات الأساسية
            'id'             => $this->id,
            'code'           => $this->code,
            'slug'           => $this->slug,

            // 2. البيانات التسويقية والرسمية
            'title_ar'       => $this->title_ar,
            'title_en'       => $this->title_en,
            'name_ar'        => $this->name_ar,
            'name_en'        => $this->name_en,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,

            // 3. البيانات التشغيلية
            'price'          => (float) $this->price,
            'duration'       => $this->duration,
            'start_date'     => $this->start_date,
            'end_date'       => $this->end_date,

            // معالجة مسار الصورة لتصبح رابطاً قابلاً للاستخدام في Frontend
            'photo_url'      => $this->photo_path ? url('storage/' . $this->photo_path) : null,

            // 4. حالة الكورس
            'is_active'      => (bool) $this->is_active,
            'is_available'   => (bool) $this->is_available,

            // 5. الإحصائيات والتفاعل
            'stats' => [
                // جلب عدد الإعجابات إما من قاعدة البيانات أو من دالة withCount
                'likes_count' => $this->whenCounted('likes', $this->likes_count, $this->total_likes),
            ],

            // منطق الإعجاب المتقدم (لا يظهر إلا إذا كان المستخدم مسجلاً وتم تحميل علاقة الإعجابات)
            'is_liked'       => $this->when($user, function () use ($user) {
                if ($this->resource->relationLoaded('likes')) {
                    return $this->likes->where('user_id', $user->id)->isNotEmpty();
                }
                return false;
            }),

            // 6. بيانات العلاقات المتداخلة (Nested Objects) باستخدام whenLoaded
            'department'     => $this->whenLoaded('department', function () {
                return [
                    'id'      => $this->department->id,
                    'name_ar' => $this->department->name_ar,
                    'name_en' => $this->department->name_en,

                    // التداخل الذكي لمعلومات المعهد عبر القسم
                    'institute' => $this->whenLoaded('department.institute', function () {
                        return [
                            'id'      => $this->department->institute->id,
                            'name_ar' => $this->department->institute->name_ar,
                            'name_en' => $this->department->institute->name_en,
                        ];
                    }),
                ];
            }),

            // 7. البيانات الإدارية المتقدمة والتعقب (مخفية عن الطلاب، تظهر فقط للسكرتير والأدمن)
            $this->mergeWhen(($user instanceof \App\Models\User) && ($user->isStatusAdmin() || $user->hasRole('secretary')), [
                'audit' => [
                    'created_by' => $this->whenLoaded('creator', function () {
                        return [
                            'id'      => $this->creator->id,
                            'name_ar' => $this->creator->name_ar,
                        ];
                    }, 'غير معروف'),

                    'updated_by' => $this->whenLoaded('updater', function () {
                        return [
                            'id'      => $this->updater->id,
                            'name_ar' => $this->updater->name_ar,
                        ];
                    }, 'لم يتم التعديل'),
                ],
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ]),
        ];
    }
}

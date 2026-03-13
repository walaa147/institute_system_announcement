<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\User;

class FavoriteInstituteService
{
    /**
     * عملية تبديل المفضلة (إضافة/إزالة)
     * تُرجع مصفوفة تحتوي على حالة العملية والرسالة
     */
    public function toggleFavorite(User $user, int $instituteId): array
    {
        // البحث هل المعهد موجود مسبقاً في مفضلة هذا الطالب تحديداً؟
        $favorite = Favorite::where('user_id', $user->id)
            ->where('institute_id', $instituteId)
            ->first();

        // إذا كان موجوداً -> نحذفه (إزالة من المفضلة)
        if ($favorite) {
            $favorite->delete();
            return [
                'is_favorited' => false,
                'message'      => 'تم إزالة المعهد من المفضلة بنجاح'
            ];
        }

        // إذا لم يكن موجوداً -> ننشئه (إضافة للمفضلة)
        Favorite::create([
            'user_id'      => $user->id,
            'institute_id' => $instituteId,
        ]);

        return [
            'is_favorited' => true,
            'message'      => 'تم إضافة المعهد إلى المفضلة بنجاح'
        ];
    }
}

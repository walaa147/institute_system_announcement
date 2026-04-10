<?php

namespace App\Policies;

use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
class AdvertisementPolicy
{
    use HandlesAuthorization;
    /**
     * السماح للسوبر أدمن بكل شيء
     */
    public function before(User $user, $ability)
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->isStatusAdmin();
    }
public function view(?User $user, Advertisement $advertisement): bool
    {
        // 1. إذا كان الإعلان مفعلاً -> مسموح للجميع (زوار ومسجلين)
        if ($advertisement->is_active === true) {
            return true;
        }

        // 2. إذا كان غير مفعل -> نتحقق من المستخدم
        if ($user) {
            // السوبر أدمن يرى كل شيء
            if ($user->hasRole('super_admin')) {
                return true;
            }

            // سكرتير المعهد يرى إعلانات معهده فقط
            if ($user->institute_id && (int)$user->institute_id === (int)$advertisement->institute_id) {
                return true;
            }
        }

        return false;
    }
    public function create(User $user): bool
    {
        return $user->isStatusAdmin();
    }

    public function update(User $user, Advertisement $advertisement): bool
    {
        return $user->institute_id === $advertisement->institute_id;
    }

    public function delete(User $user, Advertisement $advertisement): bool
    {
        return $user->institute_id === $advertisement->institute_id;
    }
}

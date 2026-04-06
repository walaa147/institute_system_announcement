<?php

namespace App\Policies;

use App\Models\Advertisement;
use App\Models\User;

class AdvertisementPolicy
{
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

    public function view(User $user, Advertisement $advertisement): bool
    {
        return $user->institute_id === $advertisement->institute_id;
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

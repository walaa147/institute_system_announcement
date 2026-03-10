<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SecretaryService
{
    public function store(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // 1. إنشاء المستخدم
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
                'institute_id' => $data['institute_id'],
            ]);
            // 2. إسناد دور سكرتير (Spatie)
            $user->assignRole('secretary');

            // 3. إنشاء ملف شخصي فارغ
            $user->profile()->create([
                'full_name_ar' => $data['name'],

            ]);

            return $user->load('institute');
        });
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return $user->refresh();
    }
    public function toggleStatus(User $user): User
    {$user->update([
            'is_active' => !$user->is_active
        ]);
        return $user->refresh();
    }

    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $user->profile()->delete();
            return $user->delete();
        });
    }
}

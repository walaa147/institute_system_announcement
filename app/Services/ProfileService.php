<?php

namespace App\Services;

use App\Services\ImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function getProfile($user)
    {
        return $user->load(['profile', 'institute']);
    }

   public function updateProfile($user, array $data)
{
    return DB::transaction(function () use ($user, $data) {
        // 1. تحديث جدول users
        if (isset($data['full_name_ar']) && !isset($data['name'])) {
            $data['name'] = $data['full_name_ar'];
        }

        $userData = array_intersect_key($data, array_flip(['name', 'email', 'password']));
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        }
        $user->update($userData);

        // 2. معالجة الصورة - التعديل هنا لضمان التحديث
        $logoPath = null;
        if (isset($data['logo'])) {
            if ($user->profile && $user->profile->logo) {
                $this->imageService->deleteImage($user->profile->logo);
            }
            // نحفظ المسار في متغير منفصل لضمان وصوله
            $logoPath = $this->imageService->updateImage($data['logo'], null, 'profiles');
        }

        // 3. تجهيز بيانات البروفايل
        $profileData = array_intersect_key($data, array_flip([
            'full_name_ar', 'full_name_en', 'phone_number',
            'gender', 'city', 'address', 'fcm_token'
        ]));

        // دمج المسار الجديد يدوياً لقطع الشك باليقين
        if ($logoPath) {
            $profileData['logo'] = $logoPath;
        }

        $profileData['profile_completed'] = true;

        // 4. الحفظ النهائي
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        return $user->refresh()->load('profile');
    });
}
    // تأمين دالة توكن الإشعارات أيضاً
    public function updateFcmToken($user, string $token)
    {
        return $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['fcm_token' => $token]
        );
    }
}

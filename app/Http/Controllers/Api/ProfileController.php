<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\UpdateProfileRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    // عرض بيانات الملف الشخصي
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('sanctum')->user();

        // الخدمة ستستخدم الآن eager loading بأمان
        $userData = $this->profileService->getProfile($user);

        return response()->json([
            'status' => true,
            'data'   => new UserResource($userData)
        ]);
    }

    // تحديث البيانات الشخصية
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('sanctum')->user();

        $updatedUser = $this->profileService->updateProfile($user, $request->validated());

        return response()->json([
            'status'  => true,
            'message' => __('validation.custom.profile.updated_success'),
            'data'    => new UserResource($updatedUser->load('profile', 'institute'))
        ]);
    }

    // تحديث توكن الإشعارات
    public function updateFcmToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => 'sometimes|nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = auth('sanctum')->user();

        // تمرير القيمة حتى لو كانت null لمسح التوكن عند تسجيل الخروج مثلاً
        $this->profileService->updateFcmToken($user, $data['fcm_token'] ?? '');

        return response()->json([
            'status'  => true,
            'message' => __('validation.custom.profile.fcm_token_updated'),
        ]);
    }
}

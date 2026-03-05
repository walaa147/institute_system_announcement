<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * تسجيل طالب جديد مع إنشاء ملفه الشخصي وإسناد الصلاحيات
     * تم تغليف العملية بـ Transaction لضمان تراجع النظام في حال حدوث أي خطأ مفاجئ
     */
    public function registerStudent(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. إنشاء سجل المستخدم الأساسي
            $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),

            ]);
            $user->assignRole('student');
            $user->profile()->create([
            'full_name_ar' =>$data['name'],  // نستخدم الاسم المدخل حالياً كاسم كامل مؤقت
            'is_active'    => true,
            'profile_completed' => false
        ]);



            // 4. إصدار توكن الدخول (Sanctum)
            $token = $user->createToken('auth_token')->plainTextToken;

            // 5. إرجاع المستخدم مع تفاصيل ملفه الشخصي والتوكن
            return [
                'user'  => $user->load('profile','roles'),
                 'token' => $token,
                'has_profile' => false // لأنه لم يدخل بياناته الشخصية بعد
            ];

        });
    }

    /**
     * تسجيل الدخول الموحد (يدعم البريد الإلكتروني أو رقم الهاتف)
     */
    public function login(array $credentials): array
    {
        $loginId = $credentials['login_id'];

        // البحث المعمق: فحص جدول المستخدمين (للإيميل) وجدول الملفات الشخصية (للهاتف)
        $user = User::where('email', $loginId)
            ->orWhereHas('profile', function ($query) use ($loginId) {
                $query->where('phone_number', $loginId);
            })->first();

        // التحقق من وجود المستخدم وصحة كلمة المرور
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login_id' =>[__('validation.custom.login_id.invalid')],
            ]);
        }

        // تحديث توكن الإشعارات (FCM) إذا تم تمريره من الموبايل
        if (isset($credentials['fcm_token'])) {
            $user->profile()->update(['fcm_token' => $credentials['fcm_token']]);
        }

        return [
            // جلب المستخدم مع ملفه الشخصي وأدواره الحالية
            'user'  => $user->load(['profile', 'roles']),
            'token' => $user->createToken('auth_token')->plainTextToken
        ];
    }

    /**
     * تسجيل الدخول أو إنشاء حساب عبر جوجل (Google OAuth)
     */
    public function loginWithGoogle(array $googleData): array
    {
        return DB::transaction(function () use ($googleData) {
            // البحث عن المستخدم باستخدام البريد الإلكتروني القادم من جوجل
            $user = User::where('email', $googleData['email'])->first();

            // إذا لم يكن مسجلاً لدينا، نقوم بإنشاء حساب طالب جديد له تلقائياً
            if (! $user) {
                $user = User::create([
                    'name'     => $googleData['name'],
                    'email'    => $googleData['email'],
                    // إنشاء كلمة مرور عشوائية معقدة لأن الدخول يتم عبر جوجل
                    'password' => Hash::make(\Illuminate\Support\Str::random(24)),
                ]);

                UserProfile::create([
                    'user_id'      => $user->id,
                    'full_name_ar' => $googleData['name'],
                    'fcm_token'    => $googleData['fcm_token'] ?? null,
                ]);

                $user->assignRole('student');
            } else {
                // إذا كان مسجلاً، نقوم بتحديث الـ FCM Token فقط إذا توفر
                if (isset($googleData['fcm_token'])) {
                    $user->profile()->update(['fcm_token' => $googleData['fcm_token']]);
                }
            }

            return [
                'user'  => $user->load(['profile', 'roles']),
                'token' => $user->createToken('auth_token')->plainTextToken
            ];
        });
    }

    /**
     * تسجيل الخروج وإتلاف التوكن الحالي
     */
    public function logout(User $user): void
    {

        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        $token?->delete();
    }
}

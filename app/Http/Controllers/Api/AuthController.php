<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class AuthController extends Controller
{ // لا تنسى استدعاء هذا الكلاس

public function register(Request $request)
{
    $data = $request->validate([
        'name' => 'required|string',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed'
    ]);

    // نستخدم الـ Transaction لضمان سلامة العملية
    return DB::transaction(function () use ($data) {

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // إذا فشل هذا السطر، سيتم حذف المستخدم تلقائياً وكأن شيئاً لم يكن
        $user->assignRole('customer');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    });
}

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }
// استبدل السطر القديم بهذا
$user = User::with('employeeProfile.institute')->where('email', $request->email)->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;
 $instituteName = ($user->employeeProfile && $user->employeeProfile->institute)
                     ? $user->employeeProfile->institute->name_ar
                     : null;
        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'role' => $user->getRoleNames()->first(),
            'institute_name' => $instituteName, // إرسال الدور للفلاتر
        ]);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. التحقق من وجود مستخدم مسجل دخول أصلاً
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'يجب  الدخول أولا'], 401);
        }

        // 2. فحص حالة حساب المستخدم (الحقل الذي أضفناه في جدول users)
        if (!$user->is_active) {
            return response()->json([
                'status' => false,
                'message' => __('validation.custom.acount.not_active')
            ], 403);
        }

        // 3. فحص حالة المعهد (للسكرتارية والطلاب فقط)
        // الـ Super Admin لا يملك institute_id لذا سيتخطى هذا الفحص
        if ($user->institute_id && !$user->institute->is_active) {
            return response()->json([
                'status' => false,
                'message' => __('validation.custom.institute.institute_not_active')
            ], 403);
        }

        return $next($request);
    }
}

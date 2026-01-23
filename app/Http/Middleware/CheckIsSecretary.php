<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIsSecretary
{
   public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    // فحص يدوي بسيط للتأكد من أن المستخدم ليس سكرتير وليس آدمن
    if (!$user->hasRole('secretary') && !$user->hasRole('admin')) {
        return response()->json([
            'status' => false,
            'message' => 'غير مصرح لك بالدخول، هذه الصلاحية للسكرتارية فقط.',
            'your_role' => $user->getRoleNames() // سطر إضافي للفحص فقط لمعرفة دورك الحالي
        ], 403);
    }

    return $next($request);
}
}

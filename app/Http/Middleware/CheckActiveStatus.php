<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class CheckActiveStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
{
     /** @var \App\Models\User $user */
     $user = Auth::user();
    // إذا كان المستخدم مسجل دخول ولكن حسابه غير نشط
    if (Auth::check() && !Auth::user()->is_active) {
        // إبطال التوكن فوراً ليخرج من النظام
      $user->tokens()->delete();

        return response()->json([
            'status' => false,
            'message' => 'عذراً، هذا الحساب معطل حالياً. يرجى مراجعة الإدارة.'
        ], 403);
    }

    return $next($request);
}
}

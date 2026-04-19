<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticateOptional
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
{
    // محاولة التعرف على المستخدم عبر sanctum دون منعه إذا لم يوجد توكن
    if ($request->bearerToken()) {
        if ($user = auth('sanctum')->user()) {
            Auth::setUser($user);
        }
    }

    return $next($request);
}
}

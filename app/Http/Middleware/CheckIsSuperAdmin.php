<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIsSuperAdmin
{
    // App\Http\Middleware\CheckIsSuperAdmin.php

public function handle(Request $request, Closure $next): Response
{
    if (!$request->user() || !$request->user()->hasRole('super_admin')) {
        return response()->json([
            'status' => false,
            'message' => 'هذا الإجراء مسموح به فقط لمدير النظام (المدير العام).',
        ], 403);
    }

    return $next($request);
}
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // إذا كانت الاستجابة JSON، أضف خيار عدم تشفير اليونيكود
        if ($response instanceof JsonResponse) {
            $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        }

        return $response;
    }
}

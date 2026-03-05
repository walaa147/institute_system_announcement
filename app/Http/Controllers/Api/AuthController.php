<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService)
    {
    }

    public function register(RegisterRequest $request)
    {
       return $this->successResponse(
           $this->authService->registerStudent($request->validated()),
            __('validation.custom.auth.register_success'),
            //201
        );
    }


    public function login(LoginRequest $request)
    {
        // إذا كان هناك توكن جوجل، نوجه الطلب لدالة جوجل، وإلا للدخول التقليدي
        $data = $request->has('google_token')
            ? $this->authService->loginWithGoogle($request->only(['email', 'name', 'fcm_token']))
            : $this->authService->login($request->validated());

        return $this->successResponse($data, __('validation.custom.auth.login_success'));
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return $this->successResponse([], __('validation.custom.auth.logout_success'));
    }
}

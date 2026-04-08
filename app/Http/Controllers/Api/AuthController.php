<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * تسجيل مستخدم جديد
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users',
            'email'    => 'required|email|unique:users',
            'phone'    => 'required|string|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $this->authService->register($request->all());
        return response()->json(["message" => "تم تسجيل المستخدم بنجاح", "user" => $user], 201);
    }

    /**
     * تسجيل دخول المستخدم
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $token = $this->authService->login($request->only('username', 'password'));
        
        if (!$token) {
            return response()->json(["error" => "بيانات الدخول غير صحيحة"], 401);
        }
        
        return response()->json($token);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();
        return response()->json(["message" => "تم تسجيل الخروج بنجاح"]);
    }

    public function refresh(): JsonResponse
    {
        $result = $this->authService->refresh();
        if (!$result) {
            return response()->json(["error" => "فشل تجديد التوكن"], 401);
        }
        return response()->json($result);
    }

    public function userProfile(): JsonResponse
    {
        // استخدام guard 'api' صراحة لضمان جلب بيانات المستخدم
        return response()->json(Auth::guard('api')->user());
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        // التحقق من الإيميل أولاً
        $validator = Validator::make($request->all(), ['email' => 'required|email']);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $this->authService->forgotPassword($request->all());
        return response()->json(["message" => "تم إرسال رابط استعادة كلمة المرور (تجريبي)"]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $this->authService->resetPassword($request->all());
        return response()->json(["message" => "تم تغيير كلمة المرور بنجاح (تجريبي)"]);
    }
}
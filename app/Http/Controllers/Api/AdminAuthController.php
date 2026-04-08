<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    protected $adminAuthService;

    public function __construct(AdminAuthService $adminAuthService)
    {
        $this->adminAuthService = $adminAuthService;
        // ملاحظة: يفضل نقل الـ Middleware إلى ملف api.php
    }

    /**
     * تسجيل دخول الأدمن باستخدام username
     */
    public function login(Request $request): JsonResponse
    {
        // التحقق من البيانات (استخدمنا username بدلاً من name)
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = $request->only('username', 'password');
        $result = $this->adminAuthService->login($credentials);

        if (!$result) {
            return response()->json(["error" => "بيانات الدخول غير صحيحة"], 401);
        }

        return response()->json($result);
    }

    /**
     * تسجيل الخروج
     */
    public function logout(): JsonResponse
    {
        // استدعينا وظيفة الـ logout من الـ service لأنها تحتوي على منطق إبطال التوكن
        $this->adminAuthService->logout();
        
        return response()->json(["message" => "تم تسجيل الخروج بنجاح"]);
    }

    /**
     * تجديد التوكن
     */
    public function refresh(): JsonResponse
    {
        $result = $this->adminAuthService->refresh();
        
        if (!$result) {
            return response()->json(["error" => "فشل تجديد التوكن"], 401);
        }

        return response()->json($result);
    }

    /**
     * عرض الملف الشخصي للأدمن
     */
    public function adminProfile(): JsonResponse
    {
        // نستخدم guard('admin') الذي قمنا بضبطه
        return response()->json(auth("admin")->user());
    }
}
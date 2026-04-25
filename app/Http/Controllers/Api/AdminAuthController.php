<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    use ApiResponse;

    protected $adminAuthService;

    public function __construct(AdminAuthService $adminAuthService)
    {
        $this->adminAuthService = $adminAuthService;
    }

    /**
     * تسجيل دخول الأدمن
     */
    public function login(Request $request): JsonResponse
    {
        // التعديل: الـ ValidationException سيُعالج تلقائياً في app.php 
        // وسيعيد كود VALIDATION_ERROR كما حددنا سابقاً.
        $validatedData = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $this->adminAuthService->login($validatedData);

        if (!$result) {
            // التعديل: توحيد كود الخطأ ليكون أكثر دقة
            return $this->errorResponse("بيانات دخول المسؤول غير صحيحة", "ADMIN_UNAUTHORIZED", 401);
        }

        return $this->successResponse($result, "تم تسجيل دخول المسؤول بنجاح");
    }

    /**
     * تسجيل الخروج
     */
    public function logout(): JsonResponse
    {
        $this->adminAuthService->logout();
        return $this->successResponse(null, "تم تسجيل الخروج بنجاح");
    }

    /**
     * تجديد التوكن الخاص بالأدمن
     */
    public function refresh(): JsonResponse
    {
        $result = $this->adminAuthService->refresh();
        
        if (!$result) {
            // التعديل: استخدام SESSION_EXPIRED لتوحيد المنطق مع الـ User Auth
            return $this->errorResponse("فشل تجديد جلسة المسؤول، يرجى الدخول مجدداً", "SESSION_EXPIRED", 401);
        }

        return $this->successResponse($result, "تم تجديد توكن المسؤول بنجاح");
    }

    /**
     * عرض الملف الشخصي للأدمن الحالي
     */
    public function adminProfile(): JsonResponse
    {
        // تأكد أن Guard 'admin' معرف في config/auth.php
        $admin = Auth::guard('admin')->user();
        
        if (!$admin) {
             return $this->errorResponse("لم يتم العثور على بيانات المسؤول", "ADMIN_NOT_FOUND", 404);
        }

        return $this->successResponse($admin, "تم جلب بيانات المسؤول بنجاح");
    }
}
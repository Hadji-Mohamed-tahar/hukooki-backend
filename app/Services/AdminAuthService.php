<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Throwable;

class AdminAuthService
{
    /**
     * تسجيل دخول الأدمن وتوليد التوكن
     */
    public function login(array $credentials)
    {
        // استخدام guard المسؤولين حصراً لضمان البحث في جدول admins
        if (!$token = Auth::guard('admin')->attempt($credentials)) {
            return false;
        }

        return $this->respondWithToken($token);
    }

    /**
     * تسجيل الخروج وإبطال التوكن آمن
     */
    public function logout(): void
    {
        try {
            // التحقق من وجود توكن قبل محاولة إبطاله لتجنب الأخطاء
            if (Auth::guard('admin')->check()) {
                JWTAuth::parseToken()->invalidate();
                Auth::guard('admin')->logout();
            }
        } catch (Throwable $e) {
            // لا نرمي خطأ هنا لأن العميل يريد الخروج بأي حال
        }
    }

    /**
     * تجديد التوكن الخاص بالأدمن
     */
    public function refresh()
    {
        try {
            // نستخدم parseToken لضمان جلب التوكن من الهيدر وتجديده
            $newToken = JWTAuth::parseToken()->refresh();
            return $this->respondWithToken($newToken);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * تنسيق الرد مع التوكن وبيانات الأدمن
     */
    protected function respondWithToken(string $token): array
    {
        return [
            "access_token" => $token,
            "token_type"   => "bearer",
            // الحصول على الـ TTL من إعدادات JWT المخصصة
            "expires_in"   => config('jwt.ttl') * 60,
            "admin"        => Auth::guard('admin')->user()
        ];
    }
}
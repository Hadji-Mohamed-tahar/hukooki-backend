<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminAuthService
{
    /**
     * تسجيل دخول الأدمن وتوليد التوكن
     */
    public function login(array $credentials)
    {
        // لاحظ هنا استخدمنا 'admin' وهو الاسم الذي وضعناه في config/auth.php
        if (!$token = Auth::guard('admin')->attempt($credentials)) {
            return false;
        }

        return $this->respondWithToken($token);
    }

    /**
     * تسجيل الخروج وإبطال التوكن (Blacklist)
     */
    public function logout()
    {
        try {
            // الطريقة الأكثر أماناً لإبطال التوكن الحالي
            JWTAuth::parseToken()->invalidate();
            return true;
        } catch (\Exception $e) {
            // في حال كان التوكن ملغى أصلاً أو غير موجود
            return false;
        }
    }

    /**
     * تجديد التوكن (Refresh)
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();
            return $this->respondWithToken($newToken);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * تنسيق الرد مع التوكن وبيانات الأدمن
     */
    protected function respondWithToken($token)
    {
        return [
            "access_token" => $token,
            "token_type" => "bearer",
            // جلب مدة الصلاحية من الإعدادات وتحويلها لثواني
            "expires_in" => JWTAuth::factory()->getTTL() * 60,
            "admin" => Auth::guard('admin')->user()
        ];
    }
}

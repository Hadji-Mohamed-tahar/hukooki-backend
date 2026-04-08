<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * تسجيل مستخدم جديد
     */
    public function register(array $data)
    {
        // تشفير كلمة المرور قبل الحفظ
        $data["password"] = Hash::make($data["password"]);
        
        // سيقوم لارفيل بحفظ username, email, phone, password بناءً على الـ fillable
        $user = User::create($data);
        
        return $user;
    }

    /**
     * تسجيل الدخول (يدعم الـ username والـ password)
     */
    public function login(array $credentials)
    {
        // نستخدم guard('api') صراحة لضمان التعامل مع جدول المستخدمين والتوكن
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return false;
        }
        
        return $this->respondWithToken($token);
    }

    /**
     * تسجيل الخروج وإبطال التوكن
     */
    public function logout()
    {
        try {
            // إبطال التوكن الحالي من الـ Blacklist
            JWTAuth::parseToken()->invalidate();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * تجديد التوكن
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
     * تنسيق الرد النهائي
     */
    protected function respondWithToken($token)
    {
        return [
            "access_token" => $token,
            "token_type" => "bearer",
            "expires_in" => JWTAuth::factory()->getTTL() * 60,
            // جلب بيانات المستخدم المسجل حالياً عبر الـ guard الصحيح
            "user" => Auth::guard('api')->user()
        ];
    }

    public function forgotPassword(array $data)
    {
        // سيتم برمجته لاحقاً عند إعداد نظام الإيميلات
        return true;
    }

    public function resetPassword(array $data)
    {
        // سيتم برمجته لاحقاً
        return true;
    }
}
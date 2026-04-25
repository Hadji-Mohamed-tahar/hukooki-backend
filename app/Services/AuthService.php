<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Facades\JWTAuth;
use Throwable;

class AuthService
{
    /**
     * تسجيل مستخدم جديد
     */
    public function register(array $data): User
    {
        $data["password"] = Hash::make($data["password"]);
        return User::create($data);
    }

    /**
     * تسجيل الدخول وجلب التوكن
     */
    public function login(array $credentials)
    {
        // محاولة الحصول على التوكن باستخدام Guard الـ API
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return false;
        }
        
        return $this->respondWithToken($token);
    }

    /**
     * تسجيل خروج آمن وإبطال التوكن
     */
    public function logout(): void
    {
        try {
            if (Auth::guard('api')->check()) {
                JWTAuth::parseToken()->invalidate();
                Auth::guard('api')->logout();
            }
        } catch (Throwable $e) {
            // تجاهل الأخطاء أثناء تسجيل الخروج لضمان تجربة مستخدم سلسة
        }
    }

    /**
     * تجديد التوكن الحالي
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();
            return $this->respondWithToken($newToken);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * إرسال رابط إعادة تعيين كلمة المرور
     * يعيد حالة الإرسال (نجاح أو فشل)
     */
    public function forgotPassword(array $data)
    {
        // يقوم بإنشاء توكن وإرسال إشعار للمستخدم تلقائياً
        return Password::broker()->sendResetLink($data);
    }

    /**
     * إعادة تعيين كلمة المرور الفعلية باستخدام التوكن المرسل
     */
    public function resetPassword(array $data): bool
    {
        // استخدام Password Broker للتحقق من التوكن وتغيير البيانات
        $status = Password::broker()->reset(
            $data,
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        // يعيد true فقط إذا كانت الحالة هي نجاح التغيير
        return $status === Password::PASSWORD_RESET;
    }

    /**
     * بناء هيكل رد التوكن الموحد
     */
    protected function respondWithToken(string $token): array
    {
        return [
            "access_token" => $token,
            "token_type"   => "bearer",
            "expires_in"   => config('jwt.ttl') * 60,
            "user"         => Auth::guard('api')->user()
        ];
    }
}
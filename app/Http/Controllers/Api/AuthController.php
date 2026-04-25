<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

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
        // لاحظ أننا لم نعد بحاجة لـ Try-Catch لأن ValidationException 
        // سيتم معالجته تلقائياً في bootstrap/app.php بالصيغة الموحدة.
        $validatedData = $request->validate([
            'username' => 'required|string|min:3|unique:users',
            'email'    => 'required|email|unique:users',
            'phone'    => 'required|string|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $this->authService->register($validatedData);
        
        return $this->successResponse($user, "تم تسجيل الحساب بنجاح", 201);
    }

    /**
     * تسجيل دخول المستخدم
     */
    public function login(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // الأفضل أن يعيد الـ Service إما مصفوفة البيانات أو يرمي Exception
        // لكن بما أن الـ Service قد يعيد null في حال فشل الدخول:
        $result = $this->authService->login($validatedData);
        
        if (!$result) {
            return $this->errorResponse("بيانات الدخول غير صحيحة", "UNAUTHORIZED_ACCESS", 401);
        }
        
        return $this->successResponse($result, "تم تسجيل الدخول بنجاح");
    }

    /**
     * تسجيل الخروج
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();
        return $this->successResponse(null, "تم تسجيل الخروج بنجاح");
    }

    /**
     * تجديد التوكن
     */
    public function refresh(): JsonResponse
    {
        $result = $this->authService->refresh();
        
        if (!$result) {
            // كود الخطأ أصبح TOKEN_EXPIRED ليتوافق مع نظامنا الجديد
            return $this->errorResponse("انتهت صلاحية الجلسة، يرجى تسجيل الدخول مجدداً", "SESSION_EXPIRED", 401);
        }
        
        return $this->successResponse($result, "تم تجديد التوكن بنجاح");
    }

    /**
     * الملف الشخصي
     */
    public function userProfile(): JsonResponse
    {
        // استخدمنا الـ Guard الموحد 'api'
        $user = Auth::guard('api')->user();
        return $this->successResponse($user, "تم جلب بيانات المستخدم");
    }

   /**
     * نسيان كلمة المرور (إرسال الرابط/الكود)
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        
        $this->authService->forgotPassword($request->only('email'));
        
        return $this->successResponse(null, "إذا كان البريد مسجلاً لدينا، فستصلك رسالة قريباً");
    }

    /**
     * تعيين كلمة مرور جديدة (resetPassword)
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'token'    => 'required|string', // الكود المستلم في البريد
            'email'    => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $result = $this->authService->resetPassword($validatedData);

        if (!$result) {
            return $this->errorResponse(
                "الكود المستخدم غير صحيح أو منتهي الصلاحية", 
                "INVALID_RESET_TOKEN", 
                400
            );
        }

        return $this->successResponse(null, "تم تغيير كلمة المرور بنجاح، يمكنك الآن تسجيل الدخول");
    }
}
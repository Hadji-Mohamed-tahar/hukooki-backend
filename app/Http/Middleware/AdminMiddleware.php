<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Traits\ApiResponse;
use Throwable;

class AdminMiddleware
{
    use ApiResponse;

    /**
     * معالجة الطلب والتأكد من أن المستخدم "أدمن"
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // 1. محاولة جلب المستخدم المرتبط بالتوكن باستخدام Guard الأدمن
            $admin = Auth::guard('admin')->user();

            // 2. التحقق من وجود المستخدم وصلاحيته كأدمن
            if (!$admin || !($admin instanceof \App\Models\Admin)) {
                return $this->errorResponse(
                    "غير مصرح لك بالوصول: هذا الحساب ليس مديراً نظام", 
                    "FORBIDDEN_ACCESS", 
                    403
                );
            }

        } catch (TokenInvalidException $e) {
            return $this->errorResponse("التوكن المرسل غير صالح", "INVALID_TOKEN", 401);
            
        } catch (TokenExpiredException $e) {
            return $this->errorResponse("انتهت صلاحية التوكن، يرجى تجديد الدخول", "TOKEN_EXPIRED", 401);
            
        } catch (JWTException $e) {
            return $this->errorResponse("مشكلة في توكن الصلاحية: غير موجود أو تالف", "JWT_ERROR", 401);
            
        } catch (Throwable $e) {
            // معالجة أي خطأ برمي غير متوقع
            return $this->errorResponse(
                "حدث خطأ أثناء معالجة الصلاحيات", 
                "AUTH_INTERNAL_ERROR", 
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }

        return $next($request);
    }
}
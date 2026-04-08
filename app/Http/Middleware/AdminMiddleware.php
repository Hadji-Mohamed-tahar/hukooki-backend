<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminMiddleware
{
    /**
     * معالجة الطلب والتأكد من أن المستخدم "أدمن"
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // 1. محاولة جلب المستخدم المرتبط بالتوكن باستخدام Guard الأدمن
            // هذا يضمن أننا نبحث في جدول admins وليس users
            $admin = Auth::guard('admin')->user();

            // 2. التحقق من وجود المستخدم وأنه يتبع موديل Admin فعلياً
            if (!$admin || !($admin instanceof \App\Models\Admin)) {
                return response()->json([
                    "status" => "error",
                    "message" => "غير مصرح لك بالوصول: هذا الحساب ليس مديراً نظام"
                ], 403);
            }

        } catch (\Exception $e) {
            // 3. معالجة حالات الخطأ الخاصة بالتوكن (JWT)
            if ($e instanceof TokenInvalidException) {
                return response()->json(["status" => "error", "message" => "التوكن غير صالح"], 401);
            } else if ($e instanceof TokenExpiredException) {
                return response()->json(["status" => "error", "message" => "انتهت صلاحية التوكن"], 401);
            } else {
                return response()->json(["status" => "error", "message" => "لم يتم العثور على توكن الصلاحية"], 401);
            }
        }

        // إذا نجحت كل الفحوصات، انتقل للطلب التالي
        return $next($request);
    }
}
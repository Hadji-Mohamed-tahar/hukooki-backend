<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use App\Traits\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // الحفاظ على تعريفات الـ Middleware الخاصة بك
        $middleware->alias([
            'is_admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            // تفعيل المعالجة الموحدة لطلبات الـ API فقط
            if ($request->is('api/*')) {
                
                // استخدام Anonymous Class للوصول إلى الـ Trait
                $apiResponse = new class { use ApiResponse; };

                // 1. أخطاء التحقق من البيانات (Validation)
                if ($e instanceof ValidationException) {
                    return $apiResponse->errorResponse(
                        "بيانات المدخلات غير صالحة",
                        "VALIDATION_ERROR",
                        422,
                        $e->errors()
                    );
                }

                // 2. أخطاء تسجيل الدخول والصلاحيات (Authentication)
                if ($e instanceof AuthenticationException) {
                    return $apiResponse->errorResponse(
                        "غير مصرح لك بالوصول: يرجى تسجيل الدخول أولاً",
                        "UNAUTHENTICATED",
                        401
                    );
                }

                // 3. أخطاء "الصفحة أو العنصر غير موجود" (404)
                if ($e instanceof NotFoundHttpException) {
                    return $apiResponse->errorResponse(
                        "المورد المطلوب غير موجود",
                        "NOT_FOUND",
                        404
                    );
                }

                // 4. أخطاء "طريقة الطلب غير مدعومة" (مثل POST بدلاً من GET)
                if ($e instanceof MethodNotAllowedHttpException) {
                    return $apiResponse->errorResponse(
                        "طريقة الطلب (Method) غير مسموح بها لهذا المسار",
                        "METHOD_NOT_ALLOWED",
                        405
                    );
                }

                // 5. أي خطأ آخر غير متوقع (Server Error)
                return $apiResponse->errorResponse(
                    "حدث خطأ داخلي في النظام",
                    "INTERNAL_SERVER_ERROR",
                    500,
                    config('app.debug') ? [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ] : null
                );
            }
        });
    })->create();
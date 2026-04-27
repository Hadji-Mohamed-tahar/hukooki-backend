<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DocumentTypeController;
use App\Http\Controllers\Api\UserDocumentController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| 1. المسارات العامة (Public Routes)
|--------------------------------------------------------------------------
*/

// هذا المسار لن يفتح صفحة، لكنه يمنع النظام من الانهيار عند توليد رابط الإيميل
Route::get('reset-password/{token}', function (string $token) {
    return response()->json(['token' => $token]);
})->middleware('guest')->name('password.reset');

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// دخول الأدمن
Route::post('admin/auth/login', [AdminAuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| 2. مسارات المستخدمين (User Guard: api)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    // إدارة الجلسة
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // الملف الشخصي
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
    });
    // تصنيفات الوثائق (مشاهدة فقط للجميع)
    Route::get('document-types', [DocumentTypeController::class, 'index']);
    Route::get('document-types/{documentType}', [DocumentTypeController::class, 'show']);


    // تصفح القوالب المتاحة
    Route::get('documents', [DocumentController::class, 'index']);
    Route::get('documents/{document}', [DocumentController::class, 'show']);
    Route::get('documents/{document}/fields', [DocumentController::class, 'getFields']);

    // العامة والخاصة معاينة الوثائق
    Route::post('documents/{document}/preview', [UserDocumentController::class, 'preview']);

    // وثائق المستخدم (المولدة)
    Route::prefix('user-documents')->group(function () {
        Route::get('/', [UserDocumentController::class, 'index']);
        Route::post('generate/{document}', [UserDocumentController::class, 'generate']);
        Route::get('{userDocument}', [UserDocumentController::class, 'show']);
        Route::get('{userDocument}/download-private', [UserDocumentController::class, 'downloadPrivate']);
    });
    // 2. رابط تحميل الوثيقة العامة (القالب الأصلي الفارغ مثلاً)
    Route::get('documents/{document}/download-public', [UserDocumentController::class, 'downloadPublic']);

    });

/*
|--------------------------------------------------------------------------
| 3. مسارات الإدارة (Admin Guard: admin)
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:admin'], 'prefix' => 'admin'], function () {

    // إدارة جلسة الأدمن
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::post('refresh', [AdminAuthController::class, 'refresh']);
        Route::get('profile', [AdminAuthController::class, 'adminProfile']);
    });

    // إدارة الأنواع والقوالب
    // ملاحظة: جعلنا الـ document-types تستثني الـ index و show لأنها موجودة في المسارات العامة
    Route::apiResource('document-types', DocumentTypeController::class)->except(['index', 'show']);
    Route::apiResource('documents', DocumentController::class);

    // إدارة الحقول (Nested Resources)
    Route::prefix('documents/{document}/fields')->group(function () {
        Route::get('/', [DocumentController::class, 'getFields']);
        Route::post('/', [DocumentController::class, 'storeField']);
        Route::put('{field}', [DocumentController::class, 'updateField']);
        Route::delete('{field}', [DocumentController::class, 'destroyField']);
    });
});

/*
|--------------------------------------------------------------------------
| 4. معالجة الروابط غير الموجودة (Fallback)
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'الرابط المطلوب غير موجود.',
        'error_code' => 'ROUTE_NOT_FOUND'
    ], 404);
});

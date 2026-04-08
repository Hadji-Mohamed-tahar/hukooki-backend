<?php

use Illuminate\Support\Facades\Route;

// استيراد الـ Controllers من المجلد الجديد Api
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DocumentTypeController;
use App\Http\Controllers\Api\UserDocumentController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| 1. مسارات المصادقة العامة (Public Auth)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// دخول الأدمن (عام)
Route::post('admin/auth/login', [AdminAuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| 2. مسارات المستخدمين المحمية (Authenticated Users)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    // الخروج وتجديد التوكن
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);

    // الملف الشخصي للمستخدم
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);

    // تصفح الوثائق المتاحة للمستخدم
    Route::get('documents', [DocumentController::class, 'index']);
    Route::get('documents/{id}', [DocumentController::class, 'show']);

    // جلب الحقول الخاصة بكل وثيقة للمستخدم العادي
    Route::middleware('auth:api')->group(function () {
        Route::get('documents/{document}/fields', [\App\Http\Controllers\Api\UserDocumentController::class, 'getFields']);
    });

    // العمليات على وثائق المستخدم (توليد وتحميل)
    Route::post('documents/{id}/generate-pdf', [UserDocumentController::class, 'generate']);
    Route::get('user-documents', [UserDocumentController::class, 'index']);
    Route::get('user-documents/{id}', [UserDocumentController::class, 'show']);
    Route::get('user-documents/{id}/download', [UserDocumentController::class, 'download']);
});

/*
|--------------------------------------------------------------------------
| 3. مسارات الإدارة المحمية (Authenticated Admin)
|--------------------------------------------------------------------------
*/
// نستخدم guard 'admin' والميدل وير الجديد 'is_admin'
Route::group(['middleware' => ['auth:admin', 'is_admin'], 'prefix' => 'admin'], function () {

    // مصادقة الأدمن
    Route::post('auth/logout', [AdminAuthController::class, 'logout']);
    Route::post('auth/refresh', [AdminAuthController::class, 'refresh']);
    Route::get('profile', [AdminAuthController::class, 'adminProfile']);

    // إدارة أنواع الوثائق (CRUD)
    Route::apiResource('document-types', DocumentTypeController::class);

    // إدارة قوالب الوثائق (CRUD)
    Route::apiResource('documents', DocumentController::class);

    // إدارة الحقول الخاصة بكل وثيقة
    Route::get('documents/{document}/fields', [DocumentController::class, 'getFields']);
    Route::post('documents/{document}/fields', [DocumentController::class, 'storeField']);
    Route::put('documents/{document}/fields/{field}', [DocumentController::class, 'updateField']);
    Route::delete('documents/{document}/fields/{field}', [DocumentController::class, 'destroyField']);
});

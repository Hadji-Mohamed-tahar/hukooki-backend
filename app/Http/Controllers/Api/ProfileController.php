<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    use ApiResponse;

    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * عرض الملف الشخصي للمستخدم الحالي
     */
    public function show(): JsonResponse
    {
        // نجلب المستخدم مباشرة من الـ Guard المعرف
        $user = Auth::guard('api')->user();

        // الـ Service يمكنها جلب علاقات إضافية إذا لزم الأمر (مثل الإحصائيات أو الوثائق الخاصة به)
        $profile = $this->profileService->getUserProfile($user);

        return $this->successResponse($profile, "تم جلب بيانات الملف الشخصي بنجاح");
    }

    /**
     * تحديث بيانات الملف الشخصي
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $validatedData = $request->validate([
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone'    => 'sometimes|string|max:20|unique:users,phone,' . $user->id,
            'password' => 'sometimes|string|min:6|confirmed',
        ]);

        // نقوم بالتحديث عبر الـ Service لضمان تشفير كلمة المرور إذا وجدت
        $updatedUser = $this->profileService->updateProfile($user, $validatedData);

        return $this->successResponse($updatedUser, "تم تحديث الملف الشخصي بنجاح");
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
        // ملاحظة: الحماية تتم عبرMiddleware في ملف api.php لضمان استقرار Laravel 11
    }

    /**
     * عرض الملف الشخصي للمستخدم الحالي
     */
    public function show(): JsonResponse
    {
        // نستخدم Auth::guard('api')->user() لضمان جلب المستخدم من التوكن الصحيح
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['error' => 'المستخدم غير موجود'], 401);
        }

        return response()->json($this->profileService->getUserProfile($user));
    }

    /**
     * تحديث بيانات الملف الشخصي
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        // التحقق من البيانات (مع مراعاة الحقول الفريدة username, email, phone)
        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone'    => 'sometimes|string|max:20|unique:users,phone,' . $user->id,
            'password' => 'sometimes|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // إرسال البيانات الموثقة للخدمة للتحديث
        $updatedUser = $this->profileService->updateProfile($user, $request->all());

        return response()->json([
            "message" => "تم تحديث الملف الشخصي بنجاح",
            "user" => $updatedUser
        ]);
    }
}
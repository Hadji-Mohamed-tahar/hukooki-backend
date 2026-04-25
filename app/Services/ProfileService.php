<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Throwable;

class ProfileService
{
    /**
     * جلب بيانات الملف الشخصي
     * يمكننا هنا تحميل العلاقات التي يحتاجها المستخدم في لوحة تحكمه
     */
    public function getUserProfile(User $user)
    {
        // مستقبلاً في Hokoki، قد ترغب في تحميل عدد الوثائق التي أنشأها المستخدم
        // $user->loadCount('documents'); 
        return $user;
    }

    /**
     * تحديث بيانات الملف الشخصي
     */
    public function updateProfile(User $user, array $data): User
    {
        // حذفنا try-catch لأن أي خطأ (مثل توقف قاعدة البيانات) 
        // سيمسكه bootstrap/app.php ويعيده كـ SERVER_ERROR موحد.

        // 1. تشفير كلمة المرور فقط إذا تم إرسالها
        if (!empty($data["password"])) {
            $data["password"] = Hash::make($data["password"]);
        } else {
            // نضمن عدم إرسال حقل الباسورد فارغاً حتى لا يمسح القيمة القديمة
            unset($data["password"]);
        }

        // 2. تحديث بيانات المستخدم
        $user->update($data);

        // 3. استخدام fresh() لضمان الحصول على أحدث البيانات من الجدول
        return $user->fresh();
    }
}
<?php

namespace App\Services;

use App\Models\DocumentType;
use Illuminate\Support\Str;
use Throwable;

class DocumentTypeService
{
    /**
     * جلب كل التصنيفات
     */
    public function getAllDocumentTypes()
    {
        return DocumentType::all();
    }

    /**
     * جلب تصنيف محدد أو رمي خطأ 404 موحد
     */
    public function getDocumentTypeById(int $id)
    {
        return DocumentType::findOrFail($id);
    }

    /**
     * إنشاء تصنيف جديد مع توليد الـ Slug
     */
    public function createDocumentType(array $data)
    {
        // حذفنا try-catch لأن app.php سيمسك أي خطأ قاعدة بيانات ويرسل رد JSON منسق
        $data["slug"] = Str::slug($data["name"]);
        
        return DocumentType::create($data);
    }

    /**
     * تحديث التصنيف وتحديث الـ Slug إذا تغير الاسم
     */
    public function updateDocumentType(int $id, array $data)
    {
        $documentType = DocumentType::findOrFail($id);

        if (isset($data["name"])) {
            $data["slug"] = Str::slug($data["name"]);
        }
        
        $documentType->update($data);
        return $documentType;
    }

    /**
     * حذف تصنيف مع حماية البيانات المرتبطة
     */
    public function deleteDocumentType(int $id)
    {
        $documentType = DocumentType::findOrFail($id);

        // إضافة منطق حماية: لا تحذف التصنيف إذا كان يحتوي على وثائق
        if ($documentType->documents()->count() > 0) {
            // سنرمي Exception عادي، والمعالج العالمي سيحوله لرد 500 أو يمكنك تخصيصه
            throw new \Exception("لا يمكن حذف التصنيف لأنه مرتبط بوثائق فعلية. قم بنقل الوثائق أولاً.");
        }

        return $documentType->delete();
    }
}
<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class UserDocumentService
{
    protected string $pythonMicroserviceUrl;

    public function __construct()
    {
        $this->pythonMicroserviceUrl = config("services.python_pdf_generator.url", 'http://localhost:8000');
    }

    /**
     * جلب جميع وثائق المستخدم مع بيانات القالب الأصلي
     */
    public function getUserDocuments(User $user)
    {
        return $user->userDocuments()->with("document")->latest()->get();
    }

    /**
     * جلب وثيقة محددة للمستخدم أو رمي خطأ 404
     */
    public function getUserDocumentById(User $user, int $userDocumentId)
    {
        return $user->userDocuments()->with("document")->findOrFail($userDocumentId);
    }

    /**
     * توليد ملف PDF عبر التواصل مع خدمة Python (WeasyPrint)
     */
    public function generatePdf(User $user, int $documentId, array $inputData)
    {
        // 1. جلب بيانات القالب
        $document = Document::findOrFail($documentId);

        // 2. التحقق من وجود ملف القالب
        $templatePath = "templates/" . $document->template_name;
        if (!Storage::disk("local")->exists($templatePath)) {
            throw new \Exception("عذراً، ملف قالب الوثيقة غير موجود على الخادم.");
        }

        $templateContent = Storage::disk("local")->get($templatePath);

        // 3. إرسال البيانات إلى Microservice (Python)
        $response = Http::timeout(60)->post($this->pythonMicroserviceUrl . "/generate-pdf", [
            "template_name"    => $document->template_name,
            "data"             => $inputData,
            "template_content" => $templateContent,
        ]);

        // 4. معالجة فشل الاستجابة من سيرفر Python
        if ($response->failed()) {
            $errorDetail = $response->json()['detail'] ?? "خطأ داخلي في خدمة توليد الملفات.";
            throw new \Exception("فشل توليد الملف: " . $errorDetail);
        }

        // 5. حفظ ملف PDF المولد في التخزين العام
        $pdfFileName = (string) Str::uuid() . ".pdf";
        $pdfPath = "pdfs/" . $pdfFileName;
        
        Storage::disk("public")->put($pdfPath, $response->body());

        // 6. تسجيل العملية في قاعدة البيانات
        return UserDocument::create([
            "user_id"            => $user->id,
            "document_id"        => $document->id,
            "generated_pdf_path" => $pdfPath,
            "input_data"         => $inputData,
        ])->load("document");
    }

    /**
     * الحصول على الرابط المباشر للملف مع فحص الملكية
     */
    public function getPdfUrl(User $user, int $userDocumentId): string
    {
        // فحص الملكية (Ownership Check)
        $userDocument = $user->userDocuments()->findOrFail($userDocumentId);

        // التأكد من وجود الملف في مجلد التخزين
        if (!Storage::disk('public')->exists($userDocument->generated_pdf_path)) {
            throw new \Exception("عذراً، ملف الـ PDF غير موجود فعلياً على الخادم.");
        }

        // تحويل المسار إلى URL كامل (مثل: https://domain.com/storage/pdfs/uuid.pdf)
        return asset(Storage::url($userDocument->generated_pdf_path));
    }
}
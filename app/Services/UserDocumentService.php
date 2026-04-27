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
     * الحصول على رابط الوثيقة الخاصة (فحص ملكية + ملف مولد)
     */
    public function getPrivatePdfUrl(User $user, int $userDocumentId): string
    {
        $userDocument = $user->userDocuments()->findOrFail($userDocumentId);

        if (!Storage::disk('public')->exists($userDocument->generated_pdf_path)) {
            throw new \Exception("عذراً، ملف الـ PDF الخاص غير موجود على الخادم.");
        }

        return asset(Storage::url($userDocument->generated_pdf_path));
    }

    /**
     * توليد وتحميل الوثيقة العامة (القالب الفارغ)
     */
    public function generatePublicPdf(int $documentId): string
    {
        $document = Document::findOrFail($documentId);

        $templatePath = "templates/" . $document->template_name;
        if (!Storage::disk("local")->exists($templatePath)) {
            throw new \Exception("عذراً، ملف قالب الوثيقة غير موجود على الخادم.");
        }

        $templateContent = Storage::disk("local")->get($templatePath);

        try {
            // أضفنا timeout أطول قليلاً لضمان عدم الانقطاع
            $response = Http::timeout(60)->post($this->pythonMicroserviceUrl . "/generate-pdf", [
                "template_name"    => $document->template_name,
                "data"             => (object)[], // تحويلها لـ object يضمن إرسالها كـ {} في JSON
                "template_content" => $templateContent,
            ]);

            if ($response->failed()) {
                // \Log::error("PDF Generation Failed: " . $response->body());
                throw new \Exception("فشل توليد الملف من محرك بايثون.");
            }

            $pdfFileName = "public_template_" . $document->id . ".pdf";
            $pdfPath = "public_documents/" . $pdfFileName;

            Storage::disk("public")->put($pdfPath, $response->body());

            return asset(Storage::url($pdfPath));
        } catch (\Exception $e) {
            // \Log::error("Connection Error: " . $e->getMessage());
            throw new \Exception("تعذر الاتصال بمحرك التوليد، تأكد من تشغيل سيرفر بايثون على بورت 8001");
        }
    }
    /**
     * توليد محتوى HTML للمعاينة مع حقن البيانات أو وضع نقاط
     */
    public function previewHtml(int $documentId, array $inputData = []): string
    {
        // 1. جلب بيانات القالب من قاعدة البيانات
        $document = Document::findOrFail($documentId);

        // 2. التحقق من وجود ملف القالب في التخزين المحلي وقراءته
        $templatePath = "templates/" . $document->template_name;
        if (!Storage::disk("local")->exists($templatePath)) {
            throw new \Exception("عذراً، ملف قالب الوثيقة غير موجود على الخادم.");
        }

        $htmlContent = Storage::disk("local")->get($templatePath);

        // 3. حقن البيانات المرسلة داخل القالب
        // نتحقق أولاً أن المصفوفة ليست فارغة لتجنب الدخول في حلقة غير ضرورية
        if (!empty($inputData)) {
            foreach ($inputData as $key => $value) {
                // النمط يعالج الحالات: {{key}} أو {{ key }} مع مسافات
                $pattern = '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/';

                // استبدال المفتاح بالقيمة (نحول القيمة لنص لضمان عدم حدوث خطأ)
                $htmlContent = preg_replace($pattern, strval($value), $htmlContent);
            }
        }

        /**
         * 4. مرحلة التنظيف (Cleaning & Filling)
         * أي حقل لم يرسل المستخدم بيانات له، يتم استبداله بنقاط متساوية
         * لكي يظهر القالب بتصميمه الأصلي كنموذج جاهز للتعبئة.
         */
        $htmlContent = preg_replace('/\{\{\s*.*?\s*\}\}/', '....................', $htmlContent);

        return $htmlContent;
    }
}

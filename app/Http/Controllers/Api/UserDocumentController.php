<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserDocumentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;

class UserDocumentController extends Controller
{
    use ApiResponse;

    protected $userDocumentService;

    public function __construct(UserDocumentService $userDocumentService)
    {
        $this->userDocumentService = $userDocumentService;
    }

    /**
     * جلب كافة وثائق المستخدم الحالي
     */
    public function index(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        $userDocuments = $this->userDocumentService->getUserDocuments($user);
        
        return $this->successResponse($userDocuments, "تم جلب قائمة وثائقك بنجاح");
    }

    /**
     * عرض تفاصيل وثيقة معينة يملكها المستخدم
     */
    public function show(int $userDocumentId): JsonResponse
    {
        $user = Auth::guard('api')->user();
        
        // الخدمة يجب أن تتحقق من ملكية المستخدم للوثيقة
        $userDocument = $this->userDocumentService->getUserDocumentById($user, $userDocumentId);
        
        return $this->successResponse($userDocument, "تم جلب تفاصيل الوثيقة");
    }

    /**
     * جلب الحقول المطلوبة لتعبئة قالب معين
     */
    public function getFields(int $documentId): JsonResponse
    {
        // بفضل المعالج العالمي، findOrFail ستعيد 404 JSON تلقائياً إذا لم يتوفر القالب
        $document = Document::with('documentFields')->findOrFail($documentId);

        $data = [
            'document_id'   => $document->id,
            'document_name' => $document->name,
            'fields'        => $document->documentFields->map(function ($field) {
                return [
                    'id'          => $field->id,
                    'name'        => $field->name,
                    'label'       => $field->label,
                    'type'        => $field->type,
                    'required'    => (bool) $field->required,
                    'placeholder' => $field->placeholder,
                ];
            })
        ];

        return $this->successResponse($data, "تم جلب حقول القالب بنجاح");
    }

    /**
     * توليد ملف PDF بناءً على البيانات المدخلة
     */
    public function generate(Request $request, int $documentId): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $request->validate([
            'input_data' => 'required|array',
        ]);

        // أي خطأ منطقي (مثل نقص بيانات) سيتم رميه كـ Exception من الـ Service
        // وسيعالجه النظام الموحد تلقائياً
        $userDocument = $this->userDocumentService->generatePdf(
            $user,
            $documentId,
            $request->input('input_data')
        );

        return $this->successResponse($userDocument, "تم توليد ملف PDF بنجاح", 201);
    }

    /**
     * الحصول على رابط تحميل ملف الـ PDF
     */
    public function download(int $userDocumentId): JsonResponse
    {
        $user = Auth::guard('api')->user();
        
        // جلب الرابط المباشر للملف مع فحص الملكية
        $fileUrl = $this->userDocumentService->getPdfUrl($user, $userDocumentId);

        return $this->successResponse([
            'download_url' => $fileUrl
        ], "تم استخراج رابط التحميل بنجاح");
    }
}
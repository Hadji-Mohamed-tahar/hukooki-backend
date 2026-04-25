<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    use ApiResponse;

    protected $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * جلب كافة القوالب
     */
    public function index(): JsonResponse
    {
        $documents = $this->documentService->getAllDocuments();
        return $this->successResponse($documents, "تم جلب قائمة القوالب بنجاح");
    }

    /**
     * جلب تفاصيل قالب محدد
     */
    public function show(int $id): JsonResponse
    {
        // الخدمة سترمي ModelNotFoundException تلقائياً إذا لم يتواجد المعرف
        // ومعالج الأخطاء العالمي سيحوله لرد 404 موحد.
        $document = $this->documentService->getDocumentById($id);
        return $this->successResponse($document, "تم جلب تفاصيل القالب");
    }

    /**
     * تخزين قالب جديد مع ملفاته
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name'             => 'required|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
            'template_file'    => 'required|file|mimes:html,txt,blade.php',
            'price'            => 'nullable|numeric|min:0',
            // 'visibility'       => 'nullable|in:free,basic,pro,private',
            'visibility' => 'nullable|in:public,private',
            'fields'           => 'nullable|array',
            'fields.*.name'    => 'required|string',
            'fields.*.type'    => 'required|string',
            'fields.*.label'   => 'nullable|string',
            'fields.*.required' => 'boolean',
        ]);

        $document = $this->documentService->createDocument($validatedData);
        return $this->successResponse($document, "تم إنشاء القالب وتخزين الملف بنجاح", 201);
    }

    /**
     * تحديث بيانات قالب
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validatedData = $request->validate([
            'name'             => 'sometimes|string|max:255',
            'document_type_id' => 'sometimes|exists:document_types,id',
            'template_file'    => 'sometimes|file|mimes:html,txt,blade.php',
            'price'            => 'sometimes|numeric|min:0',
            // 'visibility'       => 'sometimes|in:free,basic,pro,private',
            'visibility' => 'sometimes|in:public,private', // تم التعديل لتطابق الـ Migration
            'fields'           => 'nullable|array',
        ]);

        $document = $this->documentService->updateDocument($id, $validatedData);
        return $this->successResponse($document, "تم تحديث بيانات القالب بنجاح");
    }

    /**
     * حذف قالب والملفات المرتبطة
     */
    public function destroy(int $id): JsonResponse
    {
        $this->documentService->deleteDocument($id);
        return $this->successResponse(null, "تم حذف القالب والملفات المرتبطة به بنجاح");
    }

    // --- إدارة حقول الوثيقة (Document Fields) ---

    public function getFields(int $documentId): JsonResponse
    {
        $fields = $this->documentService->getDocumentFields($documentId);
        return $this->successResponse($fields, "تم جلب حقول القالب");
    }

    public function storeField(Request $request, int $documentId): JsonResponse
    {
        $validatedData = $request->validate([
            'name'     => 'required|string',
            'type'     => 'required|string',
            'label'    => 'nullable|string',
            'required' => 'boolean'
        ]);

        $field = $this->documentService->createDocumentField($documentId, $validatedData);
        return $this->successResponse($field, "تم إضافة الحقل بنجاح", 201);
    }

    public function updateField(Request $request, int $documentId, int $fieldId): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string',
            'type' => 'sometimes|string',
            'label' => 'sometimes|string',
        ]);

        $field = $this->documentService->updateDocumentField($fieldId, $validatedData);
        return $this->successResponse($field, "تم تحديث بيانات الحقل");
    }

    public function destroyField(int $documentId, int $fieldId): JsonResponse
    {
        $this->documentService->deleteDocumentField($fieldId);
        return $this->successResponse(null, "تم حذف الحقل بنجاح");
    }
}

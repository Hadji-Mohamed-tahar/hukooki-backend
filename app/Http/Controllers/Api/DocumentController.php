<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    protected $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
        // ملاحظة: الحماية تتم في ملف api.php لضمان توافق Laravel 11
    }

    public function index(): JsonResponse
    {
        $documents = $this->documentService->getAllDocuments();
        return response()->json($documents);
    }

    public function show(int $id): JsonResponse
    {
        $document = $this->documentService->getDocumentById($id);
        return response()->json($document);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
            'template_file'    => 'required|file|mimes:html,txt', // تأكد من صيغ الملفات المدعومة
            'price'            => 'nullable|numeric',
            'visibility'       => 'nullable|in:free,basic,pro,private',
            'fields'           => 'nullable|array',
            'fields.*.name'    => 'required|string',
            'fields.*.type'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // نمرر الـ request بالكامل للخدمة لأنها تعالج الملف والحقول
        $document = $this->documentService->createDocument($request->all());
        return response()->json($document, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'sometimes|string|max:255',
            'document_type_id' => 'sometimes|exists:document_types,id',
            'template_file'    => 'sometimes|file|mimes:html,txt',
            'fields'           => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $document = $this->documentService->updateDocument($id, $request->all());
        return response()->json($document);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->documentService->deleteDocument($id);
        return response()->json(['message' => 'تم حذف الوثيقة بنجاح'], 200);
    }

    // --- إدارة حقول الوثيقة (Document Fields) ---

    public function getFields(int $documentId): JsonResponse
    {
        $fields = $this->documentService->getDocumentFields($documentId);
        return response()->json($fields);
    }

    public function storeField(Request $request, int $documentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'type' => 'required|string',
            'label' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $field = $this->documentService->createDocumentField($documentId, $request->all());
        return response()->json($field, 201);
    }

    public function updateField(Request $request, int $documentId, int $fieldId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'type' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $field = $this->documentService->updateDocumentField($fieldId, $request->all());
        return response()->json($field);
    }

    public function destroyField(int $documentId, int $fieldId): JsonResponse
    {
        $this->documentService->deleteDocumentField($fieldId);
        return response()->json(['message' => 'تم حذف الحقل بنجاح'], 200);
    }
}
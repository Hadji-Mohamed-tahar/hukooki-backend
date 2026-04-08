<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentTypeController extends Controller
{
    protected $documentTypeService;

    public function __construct(DocumentTypeService $documentTypeService)
    {
        $this->documentTypeService = $documentTypeService;
        // تم نقل الميدل وير إلى ملف api.php لضمان استقرار لارفيل 11
    }

    /**
     * عرض كل التصنيفات (متاح للجميع)
     */
    public function index(): JsonResponse
    {
        $documentTypes = $this->documentTypeService->getAllDocumentTypes();
        return response()->json($documentTypes);
    }

    /**
     * عرض تصنيف محدد
     */
    public function show(int $id): JsonResponse
    {
        $documentType = $this->documentTypeService->getDocumentTypeById($id);
        return response()->json($documentType);
    }

    /**
     * إنشاء تصنيف جديد (للأدمن فقط)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:document_types',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $documentType = $this->documentTypeService->createDocumentType($request->all());
        return response()->json($documentType, 201);
    }

    /**
     * تحديث تصنيف (للأدمن فقط)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|string|max:255|unique:document_types,name,' . $id,
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $documentType = $this->documentTypeService->updateDocumentType($id, $request->all());
        return response()->json($documentType);
    }

    /**
     * حذف تصنيف (للأدمن فقط)
     */
    public function destroy(int $id): JsonResponse
    {
        $this->documentTypeService->deleteDocumentType($id);
        return response()->json(['message' => 'تم حذف تصنيف الوثائق بنجاح'], 200);
    }
}
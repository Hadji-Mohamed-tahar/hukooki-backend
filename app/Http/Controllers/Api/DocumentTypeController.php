<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    use ApiResponse;

    protected $documentTypeService;

    public function __construct(DocumentTypeService $documentTypeService)
    {
        // التحقق من الصلاحيات يتم عبر الـ Middleware في ملف الروابط
        $this->documentTypeService = $documentTypeService;
    }

    /**
     * عرض كل التصنيفات
     */
    public function index(): JsonResponse
    {
        $documentTypes = $this->documentTypeService->getAllDocumentTypes();
        return $this->successResponse($documentTypes, "تم جلب جميع التصنيفات بنجاح");
    }

    /**
     * عرض تصنيف محدد
     */
    public function show(int $id): JsonResponse
    {
        $documentType = $this->documentTypeService->getDocumentTypeById($id);
        return $this->successResponse($documentType, "تم جلب بيانات التصنيف");
    }

    /**
     * إنشاء تصنيف جديد
     * تم تحديثها لإزالة الـ description ومطابقة قاعدة البيانات
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name',
        ]);

        $documentType = $this->documentTypeService->createDocumentType($validatedData);
        
        return $this->successResponse($documentType, "تم إنشاء تصنيف الوثائق بنجاح", 201);
    }

    /**
     * تحديث تصنيف
     * تم تحديثها لإزالة الـ description وضمان التحقق من الحقول المطلوبة فقط
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255|unique:document_types,name,' . $id,
        ]);

        $documentType = $this->documentTypeService->updateDocumentType($id, $validatedData);
        
        return $this->successResponse($documentType, "تم تحديث التصنيف بنجاح");
    }

    /**
     * حذف تصنيف
     */
    public function destroy(int $id): JsonResponse
    {
        $this->documentTypeService->deleteDocumentType($id);
        
        return $this->successResponse(null, "تم حذف تصنيف الوثائق بنجاح");
    }
}
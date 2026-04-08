<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserDocumentController extends Controller
{
    protected $userDocumentService;

    public function __construct(UserDocumentService $userDocumentService)
    {
        $this->userDocumentService = $userDocumentService;
        // الحماية تتم في ملف api.php
    }

    /**
     * جلب قائمة وثائق المستخدم الحالي
     */
    public function index(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        $userDocuments = $this->userDocumentService->getUserDocuments($user);
        return response()->json($userDocuments);
    }

    /**
     * جلب تفاصيل وثيقة محددة للمستخدم
     */
    public function show(int $userDocumentId): JsonResponse
    {
        $user = Auth::guard('api')->user();
        $userDocument = $this->userDocumentService->getUserDocumentById($user, $userDocumentId);
        return response()->json($userDocument);
    }

    /**
     * جلب الحقول المطلوبة لملء وثيقة معينة
     */
    public function getFields(int $documentId): JsonResponse
    {
        // جلب الوثيقة مع الحقول الخاصة بها
        $document = \App\Models\Document::with('documentFields')->findOrFail($documentId);

        return response()->json([
            'document_id' => $document->id,
            'document_name' => $document->name,
            'fields' => $document->documentFields->map(function ($field) {
                return [
                    'id' => $field->id,
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'required' => $field->required,
                    'placeholder' => $field->placeholder,
                ];
            })
        ]);
    }
    /**
     * توليد ملف PDF جديد (ارسال البيانات لخدمة بايثون)
     */
    public function generate(Request $request, int $documentId): JsonResponse
    {
        $user = Auth::guard('api')->user();

        // التحقق من البيانات المرسلة (التي سيتم تعبئتها في القالب)
        $validator = Validator::make($request->all(), [
            'input_data' => 'required|array', // البيانات التي طلبها الأدمن في DocumentField
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $userDocument = $this->userDocumentService->generatePdf(
                $user,
                $documentId,
                $request->input('input_data')
            );

            return response()->json([
                "message" => "تم توليد ملف PDF بنجاح",
                "user_document" => $userDocument
            ], 201);
        } catch (\Exception $e) {
            return response()->json(["error" => "فشل في توليد الملف: " . $e->getMessage()], 500);
        }
    }

    /**
     * تحميل ملف PDF
     * لاحظ الإصلاح الجذري هنا باستقبال المسار من الـ Service
     */
    public function download(int $userDocumentId)
    {
        $user = Auth::guard('api')->user();

        try {
            // نأخذ المسار الفيزيائي للملف من الـ Service
            $filePath = $this->userDocumentService->getPdfPath($user, $userDocumentId);

            // الـ Controller هو من ينفذ أمر التحميل الفعلي
            return response()->download($filePath);
        } catch (\Exception $e) {
            return response()->json(["error" => "الملف غير موجود أو لا تملك صلاحية الوصول"], 404);
        }
    }
}

<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * إرسال استجابة نجاح موحدة.
     */
    public function successResponse($data = null, string $message = "تمت العملية بنجاح", int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * إرسال استجابة خطأ موحدة.
     */
    public function errorResponse(string $message, string $errorCode, int $statusCode = 400, $data = null): JsonResponse
    {
        return response()->json([
            'success'    => false,
            'message'    => $message,
            'error_code' => $errorCode,
            'data'       => $data,
        ], $statusCode);
    }
}
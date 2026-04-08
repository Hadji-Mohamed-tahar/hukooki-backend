<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse($data = null, string $message = "تمت العملية بنجاح", int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'error'   => null,
            'status'  => $code
        ], $code);
    }

    protected function errorResponse(string $message, string $errorCode, int $code = 400, $details = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'error'   => $errorCode,
            'details' => $details,
            'status'  => $code
        ], $code);
    }
}
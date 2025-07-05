<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    protected function successResponse(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'status' => $status,
        ];
        if (!is_null($data)) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }

    protected function errorResponse(string $message, mixed $data = null, int $status = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'status' => $status,
        ];
        if (!is_null($data)) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }
}

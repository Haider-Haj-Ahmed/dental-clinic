<?php

namespace App\Http\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ApiResponse
{
    protected function ok(mixed $data = null, ?string $message = null, array $meta = []): JsonResponse
    {
        return $this->successResponse($data, $message, 200, $meta);
    }

    protected function created(mixed $data = null, ?string $message = null, array $meta = []): JsonResponse
    {
        return $this->successResponse($data, $message, 201, $meta);
    }

    protected function noContentResponse(): Response
    {
        return response()->noContent();
    }

    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function errorResponse(
        string $message,
        int $status = 400,
        array $errors = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}

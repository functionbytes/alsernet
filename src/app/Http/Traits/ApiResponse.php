<?php

namespace App\Http\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function apiSuccess(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        $response = ['success' => true, 'message' => $message];

        if (! is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    protected function apiError(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $response = ['success' => false, 'message' => $message];

        if (! is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    protected function paginated(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}

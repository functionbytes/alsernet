<?php

namespace App\Http\Api\V1\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponses
{
    protected function ok(mixed $data, string $message = 'ok', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data instanceof JsonResource ? $data->toArray(request()) : $data,
        ], $status);
    }

    protected function created(mixed $data, string $message = 'Creado.'): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    protected function noContent(string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], 200);
    }

    protected function paginated(LengthAwarePaginator $paginator, string $resource): JsonResponse
    {
        $items = $resource::collection($paginator);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => $items->toArray(request()),
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'lastPage' => $paginator->lastPage(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    protected function errorResponse(string $message, string $code, int $status, ?array $errors = null): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
            'code' => $code,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}

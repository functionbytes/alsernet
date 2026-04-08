<?php

namespace Modules\Reviews\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = [],
        array $links = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        if (! empty($links)) {
            $response['links'] = $links;
        }

        return response()->json($response, $statusCode);
    }

    public static function error(
        string $message,
        int $statusCode = 400,
        array $errors = [],
        ?string $requestId = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        if ($requestId) {
            $response['request_id'] = $requestId;
        }

        return response()->json($response, $statusCode);
    }

    public static function paginated(
        ResourceCollection $collection,
        string $message = 'Success',
        array $additionalMeta = []
    ): JsonResponse {
        $paginator = $collection->resource;

        $meta = array_merge([
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ], $additionalMeta);

        $links = [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];

        return self::success(
            data: $collection->resolve(),
            message: $message,
            meta: $meta,
            links: $links
        );
    }

    public static function resource(
        JsonResource $resource,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return self::success(
            data: $resource->resolve(),
            message: $message,
            statusCode: $statusCode
        );
    }
}

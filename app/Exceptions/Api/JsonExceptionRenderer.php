<?php

namespace App\Exceptions\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class JsonExceptionRenderer
{
    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $this->shouldHandle($request)) {
            return null;
        }

        return match (true) {
            $e instanceof ValidationException => $this->validationError($e),
            $e instanceof AuthenticationException => $this->authenticationError(),
            $e instanceof AuthorizationException,
            $e instanceof AccessDeniedHttpException,
            $e instanceof HttpException && $e->getStatusCode() === 403 => $this->authorizationError(),
            $e instanceof HttpException && $e->getStatusCode() === 422 => $this->unprocessableError($e),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => $this->notFoundError(),
            $e instanceof TooManyRequestsHttpException => $this->tooManyRequestsError($e),
            $e instanceof MethodNotAllowedHttpException => $this->methodNotAllowedError(),
            default => $this->serverError($e),
        };
    }

    private function shouldHandle(Request $request): bool
    {
        return str_starts_with($request->path(), 'api/v1') || $request->expectsJson();
    }

    private function validationError(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Datos inválidos.',
            'errors' => $e->errors(),
            'code' => 'VALIDATION_ERROR',
        ], 422);
    }

    private function authenticationError(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'No autenticado.',
            'code' => 'UNAUTHENTICATED',
        ], 401);
    }

    private function authorizationError(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'No autorizado.',
            'code' => 'FORBIDDEN',
        ], 403);
    }

    private function unprocessableError(HttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage() ?: 'Datos inválidos.',
            'code' => 'VALIDATION_ERROR',
        ], 422);
    }

    private function notFoundError(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Recurso no encontrado.',
            'code' => 'NOT_FOUND',
        ], 404);
    }

    private function tooManyRequestsError(TooManyRequestsHttpException $e): JsonResponse
    {
        $retryAfter = $e->getHeaders()['Retry-After'] ?? null;

        return response()->json([
            'success' => false,
            'message' => 'Demasiadas solicitudes. Inténtalo más tarde.',
            'code' => 'TOO_MANY_REQUESTS',
        ], 429, $retryAfter ? ['Retry-After' => $retryAfter] : []);
    }

    private function methodNotAllowedError(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Método HTTP no permitido.',
            'code' => 'METHOD_NOT_ALLOWED',
        ], 405);
    }

    private function serverError(Throwable $e): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => 'Error interno del servidor.',
            'code' => 'SERVER_ERROR',
        ];

        if (app()->hasDebugModeEnabled()) {
            $body['debug'] = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        return response()->json($body, 500);
    }
}

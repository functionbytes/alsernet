<?php

namespace Modules\Mailer\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Helper para responder con un shape consistente en los endpoints JSON del módulo.
 *
 * Shape canónico:
 *  - Éxito: { "success": true, "data": { ... }, ...extras }
 *  - Error: { "success": false, "error": { "code": "...", "message": "..." }, "message": "...", ...extras }
 *
 * El campo `message` se mantiene en errores por retro-compatibilidad con clientes existentes.
 */
trait BuildsJsonResponses
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $extras
     */
    protected function jsonSuccess(array $data = [], int $status = 200, array $extras = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'data' => $data,
        ], $extras), $status);
    }

    /**
     * @param  array<string, mixed>  $extras
     */
    protected function jsonError(string $code, string $message, int $status = 400, array $extras = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
            'message' => $message,
        ], $extras), $status);
    }
}

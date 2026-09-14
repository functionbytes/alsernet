<?php

namespace Modules\Reviews\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\ReviewIngestor;

/**
 * Recibe lo que manda la tienda.
 *
 * La bandeja de salida de PrestaShop reintenta hasta cuatro horas, así que la
 * misma opinión puede llegar varias veces: todo el procesamiento es idempotente
 * por `ps_comment_id`.
 */
class ReviewEventReceiverController extends Controller
{
    public function __construct(private readonly ReviewIngestor $ingestor) {}

    public function handle(Request $request): JsonResponse
    {
        $event = (string) $request->header('X-Alsernet-Event', '');
        $payload = (array) $request->input('data', []);

        try {
            $result = match ($event) {
                'review.created', 'review.updated' => $this->ingestor->upsert($payload),
                'review.deleted' => $this->ingestor->markDeleted((int) ($payload['id_productcomment'] ?? 0)),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Reviews: fallo al procesar el evento de la tienda.', [
                'event' => $event,
                'ps_comment_id' => $payload['id_productcomment'] ?? null,
                'error' => $e->getMessage(),
            ]);

            // 500 para que la tienda lo reintente en vez de darlo por entregado.
            return response()->json(['ok' => false, 'error' => 'processing error'], 500);
        }

        if ($result === null) {
            Log::info('Reviews: evento desconocido.', ['event' => $event]);

            return response()->json(['ok' => true, 'ignored' => true]);
        }

        return response()->json([
            'ok' => true,
            'review_id' => $result instanceof Review ? $result->id : null,
        ]);
    }
}

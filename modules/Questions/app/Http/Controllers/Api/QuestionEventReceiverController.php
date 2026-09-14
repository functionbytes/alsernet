<?php

namespace Modules\Questions\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Questions\Models\Question;
use Modules\Questions\Services\QuestionIngestor;

class QuestionEventReceiverController extends Controller
{
    public function __construct(private readonly QuestionIngestor $ingestor) {}

    public function handle(Request $request): JsonResponse
    {
        $event = (string) $request->header('X-Alsernet-Event', '');
        $payload = (array) $request->input('data', []);

        try {
            $result = match ($event) {
                'question.created', 'question.updated' => $this->ingestor->upsert($payload),
                'question.deleted' => $this->ingestor->markDeleted((int) ($payload['id_question'] ?? 0)),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Questions: fallo al procesar el evento de la tienda.', [
                'event' => $event,
                'ps_question_id' => $payload['id_question'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'error' => 'processing error'], 500);
        }

        if ($result === null) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        return response()->json([
            'ok' => true,
            'question_id' => $result instanceof Question ? $result->id : null,
        ]);
    }
}

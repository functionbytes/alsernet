<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\AI\SuggestReplyService;

class AiSuggestionsController extends Controller
{
    public function __construct(
        private readonly SuggestReplyService $suggestService,
    ) {}

    /**
     * Return AI-generated reply suggestions for a conversation, along with
     * the last customer message they are meant to answer.
     */
    public function __invoke(Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $suggestions = $this->suggestService->suggest($conversation);

        return response()->json([
            'success' => true,
            'message' => $suggestions === []
                ? 'El asistente de IA no está disponible en este momento.'
                : 'Sugerencias generadas correctamente.',
            'data' => [
                'suggestions' => $suggestions,
                'last_message' => $this->lastCustomerMessage($conversation),
            ],
        ]);
    }

    /**
     * Plain-text body of the most recent non-internal customer message.
     */
    private function lastCustomerMessage(Conversation $conversation): string
    {
        $item = $conversation->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->whereNotNull('author_id')
            ->latest()
            ->first();

        return strip_tags($item?->body ?? '');
    }
}

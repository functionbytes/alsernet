<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\AI\ArticleSuggestionService;

/**
 * Sugerencias de artículos de conocimiento para el composer del inbox:
 * devuelve los artículos relevantes al último mensaje del cliente para que el
 * agente inserte el enlace/extracto en su respuesta. El resultado viene
 * cacheado por (conversación, último mensaje) desde ArticleSuggestionService.
 */
class SuggestedArticlesController extends Controller
{
    public function __construct(
        private readonly ArticleSuggestionService $suggestionService,
    ) {}

    public function __invoke(Conversation $conversation): JsonResponse
    {
        $result = $this->suggestionService->suggest($conversation);

        return response()->json([
            'success' => true,
            'message' => $result['suggestions'] === []
                ? 'No se encontraron artículos relevantes para esta conversación.'
                : 'Sugerencias de artículos generadas correctamente.',
            'data' => [
                'query' => $result['query'],
                'suggestions' => $result['suggestions'],
            ],
        ]);
    }
}

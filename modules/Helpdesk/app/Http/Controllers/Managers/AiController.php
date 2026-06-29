<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\AI\DeepLTranslationService;
use Modules\Helpdesk\Services\AI\SuggestReplyService;

class AiController extends Controller
{
    public function __construct(
        private readonly SuggestReplyService $suggestService,
        private readonly DeepLTranslationService $translationService,
    ) {
        $this->middleware('can:helpdesk.conversations.update');
    }

    /**
     * Return AI-generated reply suggestions for a conversation.
     */
    public function suggestReplies(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $suggestions = $this->suggestService->suggest($conversation);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Translate a conversation item body using DeepL.
     */
    public function translateItem(Request $request, ConversationItem $item): JsonResponse
    {
        $this->authorize('view', $item->conversation);

        $request->validate([
            'target' => ['required', 'string', 'min:2', 'max:5'],
        ]);

        $text = strip_tags($item->body ?? '');

        if (empty($text)) {
            return response()->json(['success' => false, 'message' => 'El item no tiene texto para traducir.'], 422);
        }

        $translated = $this->translationService->translate($text, $request->input('target', 'es'));

        if ($translated === null) {
            return response()->json(['success' => false, 'message' => 'El servicio de traducción no está disponible.'], 503);
        }

        return response()->json([
            'success' => true,
            'translated' => $translated,
        ]);
    }
}

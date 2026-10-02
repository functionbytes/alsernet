<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Http\Requests\ConversationPresenceHeartbeatRequest;
use Modules\Helpdesk\Http\Requests\ConversationPresenceOverviewRequest;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Conversations\ConversationPresenceService;

/**
 * "Quién está viendo" una conversación: el panel late contra heartbeat() y la
 * bandeja consulta overview() para pintar los avatares en las tarjetas.
 */
class ConversationPresenceController extends Controller
{
    public function __construct(private readonly ConversationPresenceService $presence) {}

    public function heartbeat(ConversationPresenceHeartbeatRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $user = $request->user();
        $this->presence->touch($conversation, $user, $request->validated('action') ?? 'viewing');

        return response()->json([
            'viewers' => $this->presence->viewers($conversation->id, $user->id),
        ]);
    }

    public function leave(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $this->presence->leave($conversation, $request->user());

        return response()->json(['success' => true]);
    }

    public function overview(ConversationPresenceOverviewRequest $request): JsonResponse
    {
        $user = $request->user();
        $ids = $request->conversationIds();

        if ($ids === []) {
            return response()->json(['data' => (object) []]);
        }

        // Primero el cache (barato); la política solo se evalúa para las
        // conversaciones que realmente tienen a alguien dentro.
        $withViewers = $this->presence->viewersForMany($ids, $user->id);

        $visible = $withViewers === []
            ? collect()
            : Conversation::query()
                ->whereIn('id', array_keys($withViewers))
                ->get(['id', 'inbox_id', 'assignee_id'])
                ->filter(fn (Conversation $conversation) => $user->can('view', $conversation));

        $data = $visible
            ->mapWithKeys(fn (Conversation $conversation) => [$conversation->id => $withViewers[$conversation->id]])
            ->all();

        return response()->json(['data' => (object) $data]);
    }
}

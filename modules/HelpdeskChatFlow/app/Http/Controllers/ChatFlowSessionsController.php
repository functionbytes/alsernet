<?php

namespace Modules\HelpdeskChatFlow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;
use Modules\HelpdeskChatFlow\Services\ChatFlowReplayService;

/**
 * Sesiones en ejecución de un flow: listado, replay y toma de control por un supervisor.
 */
class ChatFlowSessionsController extends Controller
{
    public function index(ChatFlow $chatFlow): View
    {
        $this->authorize('view', $chatFlow);

        $sessions = $chatFlow->sessions()
            ->with('conversation.customer')
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('started_at')
            ->paginate(30)
            ->withQueryString();

        return view('chatflow::sessions', compact('chatFlow', 'sessions'));
    }

    public function replay(ChatFlow $chatFlow, ChatFlowSession $session, ChatFlowReplayService $replay): View
    {
        $this->authorize('view', $chatFlow);
        abort_unless($session->chat_flow_id === $chatFlow->id, 404);

        $result = $replay->replay($session);

        return view('chatflow::replay', compact('chatFlow', 'session', 'result'));
    }

    /**
     * Supervisor takes over a conversation the bot is handling: stops the bot
     * session, releases it to the inbox and assigns it to the current agent.
     */
    public function takeOver(int $conversationId, ChatFlowEngine $engine): JsonResponse
    {
        $this->authorize('takeOver', ChatFlow::class);

        $conversation = Conversation::on('helpdesk')->findOrFail($conversationId);

        // Only act on conversations actually handled by the bot — keeps the
        // endpoint scoped to its purpose (the route is already gated to supervisors).
        abort_unless($conversation->metadata['handled_by_bot'] ?? false, 422, 'La conversación no está siendo atendida por el bot.');

        $engine->takeOver($conversation, (int) auth()->id());

        return response()->json(['success' => true, 'message' => 'Has tomado el control de la conversación.']);
    }
}

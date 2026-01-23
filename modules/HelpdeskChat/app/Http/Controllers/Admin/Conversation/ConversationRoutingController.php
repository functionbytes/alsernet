<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Conversation;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Services\ConversationRoutingService;

class ConversationRoutingController extends Controller
{
    public function __construct(
        protected ConversationRoutingService $routingService
    ) {}

    /**
     * Auto-assign conversation to best available agent.
     */
    public function autoAssign(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validate([
            'strategy' => 'nullable|in:round_robin,least_busy,balanced,skilled,availability',
        ]);

        $agent = $this->routingService->autoAssign(
            $conversation,
            $validated['strategy'] ?? null
        );

        if (! $agent) {
            return response()->json([
                'success' => false,
                'message' => 'No available agents found for assignment.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Conversation assigned to {$agent->name}.",
            'data' => [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'agent_email' => $agent->email,
            ],
        ]);
    }

    /**
     * Get routing statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $accountId = auth()->user()->account_id;

        $validated = $request->validate([
            'inbox_id' => 'nullable|exists:inboxes,id',
        ]);

        $stats = $this->routingService->getRoutingStats(
            $accountId,
            $validated['inbox_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Suggest best agent for manual assignment.
     */
    public function suggest(Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $suggestion = $this->routingService->suggestAgent($conversation);

        if (! $suggestion) {
            return response()->json([
                'success' => false,
                'message' => 'No available agents found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $suggestion,
        ]);
    }
}

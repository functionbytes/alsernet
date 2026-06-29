<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Services\AgentPresenceService;

class AgentPresenceController extends Controller
{
    public function __construct(
        private readonly AgentPresenceService $presenceService
    ) {}

    /**
     * POST /panel/helpdesk/presence/heartbeat
     * Keep agent marked as online; call every ~60s from frontend.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $this->presenceService->heartbeat($userId);

        // Auto-set to available on first heartbeat if currently offline
        $currentState = $this->presenceService->getState($userId);

        if ($currentState === AgentSettings::PRESENCE_OFFLINE) {
            $this->presenceService->setState($userId, AgentSettings::PRESENCE_AVAILABLE);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * POST /panel/helpdesk/presence/state
     * Manually set agent presence state.
     */
    public function setState(Request $request): JsonResponse
    {
        $request->validate([
            'state' => ['required', 'string', 'in:'.implode(',', AgentSettings::PRESENCE_STATES)],
        ]);

        $this->presenceService->setState($request->user()->id, $request->input('state'));

        return response()->json(['ok' => true, 'state' => $request->input('state')]);
    }

    /**
     * GET /panel/helpdesk/presence/agents
     * List all agents with their presence state.
     */
    public function list(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.conversations.view');

        $agents = $this->presenceService->getAgentsList(
            $request->integer('inbox_id') ?: null
        );

        return response()->json(['agents' => $agents]);
    }
}

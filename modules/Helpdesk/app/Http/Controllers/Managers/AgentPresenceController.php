<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Jobs\ReturnAgentToAvailableJob;
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
        $validated = $request->validate([
            'state' => ['required', 'string', 'in:'.implode(',', AgentSettings::PRESENCE_STATES)],
            'away_message' => ['nullable', 'string', 'max:500'],
            'auto_return' => ['nullable', 'string', 'in:manual,1h,4h,tomorrow'],
            'reassign' => ['nullable', 'string', 'in:keep,team,agent'],
            'reassign_agent_id' => ['nullable', 'integer', 'exists:users,id', 'required_if:reassign,agent'],
            'timezone' => ['nullable', 'timezone'],
        ]);

        $userId = $request->user()->id;

        // Estado previo (columna cruda) para reasignar solo cuando el agente
        // realmente pasa de "disponible" a ausente, no en cada re-guardado del modal.
        $previousState = AgentSettings::query()->where('user_id', $userId)->value('presence_state')
            ?? AgentSettings::PRESENCE_OFFLINE;

        $preferences = null;
        $returnAt = null;

        if ($request->hasAny(['away_message', 'auto_return', 'reassign'])) {
            $autoReturn = $validated['auto_return'] ?? 'manual';
            $returnAt = $this->computeReturnAt($autoReturn, $validated['state'], $validated['timezone'] ?? null);
            $preferences = [
                'away_message' => $validated['away_message'] ?? '',
                'auto_return' => $autoReturn,
                'reassign' => $validated['reassign'] ?? 'keep',
                'reassign_agent_id' => $validated['reassign_agent_id'] ?? null,
                'auto_return_at' => $returnAt?->toIso8601String(),
            ];
        }

        $this->presenceService->setState($userId, $validated['state'], $preferences);

        // Al ausentarse con "A mi equipo" o "A un agente específico", liberar sus
        // conversaciones abiertas — solo en una transición REAL a un estado no
        // disponible, para no re-liberar (ni robar asignaciones manuales del
        // supervisor) al re-guardar el modal estando ya ausente.
        $reassigned = 0;
        $becameUnavailable = $validated['state'] !== $previousState
            && $validated['state'] !== AgentSettings::PRESENCE_AVAILABLE;
        $reassignMode = $validated['reassign'] ?? 'keep';
        if ($becameUnavailable && $reassignMode !== 'keep') {
            $toUserId = $reassignMode === 'agent' ? (int) $validated['reassign_agent_id'] : null;
            $reassigned = $this->presenceService->reassignActiveConversations($userId, $toUserId);
        }

        // Programar la vuelta automática a "disponible" (En 1 hora / 4 horas / Mañana 09:00).
        if ($returnAt) {
            ReturnAgentToAvailableJob::dispatch($userId, $returnAt->toIso8601String())->delay($returnAt);
        }

        return response()->json([
            'ok' => true,
            'state' => $validated['state'],
            'reassigned' => $reassigned,
            'auto_return_at' => $returnAt?->toIso8601String(),
        ]);
    }

    /**
     * Absolute time the agent should be flipped back to "available", or null
     * when there is no auto-return (manual, or the new state is already available).
     */
    private function computeReturnAt(string $autoReturn, string $state, ?string $timezone = null): ?Carbon
    {
        if ($state === AgentSettings::PRESENCE_AVAILABLE || $autoReturn === 'manual') {
            return null;
        }

        // "Mañana 09:00" se calcula en la zona horaria del agente y se guarda en UTC.
        $tz = $timezone ?: config('app.timezone');

        return match ($autoReturn) {
            '1h' => now()->addHour(),
            '4h' => now()->addHours(4),
            'tomorrow' => now($tz)->addDay()->setTime(9, 0)->utc(),
            default => null,
        };
    }

    /**
     * GET /panel/helpdesk/presence/me
     * Current agent presence state + away-mode preferences (hydrates the modal).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->presenceService->getSettings($request->user()->id));
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

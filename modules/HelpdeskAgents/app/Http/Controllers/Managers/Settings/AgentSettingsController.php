<?php

namespace Modules\HelpdeskAgents\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\HelpdeskAgents\Concerns\InteractsWithDefaultAiAgent;
use Modules\HelpdeskAgents\Http\Requests\GetAiAgentModelsRequest;
use Modules\HelpdeskAgents\Http\Requests\TestAiAgentConnectionRequest;
use Modules\HelpdeskAgents\Http\Requests\UpdateAiAgentSettingsRequest;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Services\LlmConnectionTesterService;

class AgentSettingsController extends Controller
{
    use InteractsWithDefaultAiAgent;

    public function __construct(
        private readonly LlmConnectionTesterService $connectionTester
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', AiAgent::class);

        $agent = $this->getDefaultAgent() ?? new AiAgent;

        $providers = config('helpdeskagents.providers');

        $statuses = [
            'inactive' => 'Inactivo',
            'active' => 'Activo',
            'paused' => 'En Pausa',
        ];

        return view('helpdeskagents::managers.ai-agent.settings', [
            'agent' => $agent,
            'providers' => $providers,
            'statuses' => $statuses,
            'hasAgent' => AiAgent::exists(),
        ]);
    }

    public function update(UpdateAiAgentSettingsRequest $request): RedirectResponse
    {
        $this->authorize('update', AiAgent::class);

        $validated = $request->validated();

        $agent = $this->getDefaultAgent() ?? new AiAgent;

        $agent->name = $validated['name'];
        $agent->description = $validated['description'];
        $agent->provider = $validated['provider'];
        $agent->model = $validated['model'];
        $agent->personality = $validated['personality'];
        $agent->status = $validated['status'];

        if (filled($validated['api_key'] ?? null)) {
            $agent->api_key_encrypted = $validated['api_key'];
        }

        $parameters = [
            'temperature' => (float) ($validated['temperature'] ?? 0.7),
            'max_tokens' => (int) ($validated['max_tokens'] ?? 2048),
            'top_p' => (float) ($validated['top_p'] ?? 1.0),
            'frequency_penalty' => (float) ($validated['frequency_penalty'] ?? 0),
            'presence_penalty' => (float) ($validated['presence_penalty'] ?? 0),
        ];

        $parameters = match ($validated['provider']) {
            'openai' => array_merge($parameters, ['organization_id' => $validated['organization_id'] ?? null]),
            'anthropic' => array_merge($parameters, ['version' => $validated['version'] ?? '2023-06-01']),
            'local' => array_merge($parameters, ['base_url' => $validated['base_url'] ?? 'http://localhost:11434']),
            default => $parameters,
        };

        $agent->parameters = $parameters;

        if ($validated['status'] === 'active' && ! $agent->enabled_at) {
            $agent->enabled_at = now();
        }

        $agent->save();

        $this->forgetDefaultAgent();

        return redirect()
            ->route('helpdesk.ai.settings')
            ->with('success', 'Configuración del agente IA actualizada correctamente');
    }

    public function testConnection(TestAiAgentConnectionRequest $request): JsonResponse
    {
        $this->authorize('manage', AiAgent::class);

        try {
            $this->connectionTester->test($request->validated());

            return response()->json(['success' => true, 'message' => 'Conexión exitosa']);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getModels(GetAiAgentModelsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AiAgent::class);

        $provider = config("helpdeskagents.providers.{$request->provider}");
        $models = $provider['models'] ?? [];

        return response()->json(['models' => $models]);
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', AiAgent::class);

        $agent = $this->getDefaultAgent();

        if (! $agent) {
            return response()->json(['error' => 'No agent configured'], 404);
        }

        $sessionCounts = $agent->sessions()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw('AVG(CASE WHEN ended_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, started_at, ended_at) END) as avg_duration')
            ->first();

        $totalMessages = $agent->sessions()
            ->join('helpdesk_ai_agent_session_messages', 'helpdesk_ai_agent_sessions.id', '=', 'helpdesk_ai_agent_session_messages.session_id')
            ->count();

        return response()->json([
            'total_sessions' => (int) ($sessionCounts->total ?? 0),
            'active_sessions' => (int) ($sessionCounts->active ?? 0),
            'completed_sessions' => (int) ($sessionCounts->completed ?? 0),
            'failed_sessions' => (int) ($sessionCounts->failed ?? 0),
            'total_messages' => $totalMessages,
            'average_session_duration' => round((float) ($sessionCounts->avg_duration ?? 0), 1),
            'enabled_at' => $agent->enabled_at?->toIso8601String(),
            'status' => $agent->status,
        ]);
    }
}

<?php

namespace Modules\HelpdeskAgents\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\HelpdeskAgents\Concerns\InteractsWithDefaultAiAgent;
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
}

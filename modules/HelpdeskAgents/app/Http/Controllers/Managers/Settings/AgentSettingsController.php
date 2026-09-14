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
use Modules\HelpdeskAgents\Models\AiUsage;
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
            'usage' => $this->monthlyUsage(),
        ]);
    }

    /**
     * Consumo del mes en curso, para la tarjeta del rail: cuántas llamadas al
     * modelo y cuántos tokens (entrada + salida) llevan gastados.
     *
     * @return array{calls: int, tokens: int}
     */
    private function monthlyUsage(): array
    {
        $row = AiUsage::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('COUNT(*) as calls, COALESCE(SUM(tokens_in + tokens_out), 0) as tokens')
            ->first();

        return [
            'calls' => (int) ($row->calls ?? 0),
            'tokens' => (int) ($row->tokens ?? 0),
        ];
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

        // Este método es la única vía que crea el primer AiAgent del módulo.
        // Sin is_default=true, getDefaultAgent() lo sigue encontrando por su
        // fallback a "el más antiguo" — pero un segundo agente creado después
        // (sin marcar ninguno is_default) le robaría el puesto en cualquier
        // sitio que filtre por default() primero.
        if (! $agent->exists) {
            $agent->is_default = true;
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

        $config = $request->validated();

        // El campo de la clave nunca vuelve a pintar la guardada (a propósito),
        // así que el caso normal es probar con el campo vacío. Sin este relleno,
        // el tester caía al setting global (que no suele existir) y "Probar"
        // respondía "API key no configurada" aunque el agente tuviera clave.
        if (blank($config['api_key'] ?? null)) {
            $stored = $this->getDefaultAgent()?->getApiKey();

            if (filled($stored)) {
                $config['api_key'] = $stored;
            }
        }

        try {
            $this->connectionTester->test($config);

            return response()->json(['success' => true, 'message' => 'Conexión exitosa']);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

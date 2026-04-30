<?php

namespace Modules\Chat\Services\Ai;

use Illuminate\Support\Facades\Http;
use Modules\Chat\Models\Ai\AiAgent;

class AiAgentConfigService
{
    /**
     * Provider definitions: label, available models, icon.
     *
     * @return array<string, array{label: string, models: list<string>, icon: string}>
     */
    public function providers(): array
    {
        return [
            'openai' => [
                'label' => 'OpenAI (GPT-4)',
                'models' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'],
                'icon' => '🟢',
            ],
            'anthropic' => [
                'label' => 'Anthropic (Claude)',
                'models' => ['claude-3-opus-20250219', 'claude-3-sonnet-20250229', 'claude-3-haiku-20250307'],
                'icon' => '🔵',
            ],
            'gemini' => [
                'label' => 'Google Gemini',
                'models' => ['gemini-2.0-flash', 'gemini-1.5-pro', 'gemini-1.5-flash'],
                'icon' => '🟡',
            ],
            'local' => [
                'label' => 'Local Model (Ollama)',
                'models' => ['llama2', 'mistral', 'neural-chat', 'openchat'],
                'icon' => '⚪',
            ],
        ];
    }

    /**
     * Status options (value => label).
     *
     * @return array<string, string>
     */
    public function statuses(): array
    {
        return [
            'inactive' => 'Inactivo',
            'active' => 'Activo',
            'paused' => 'En Pausa',
        ];
    }

    /**
     * Model list for a given provider.
     *
     * @return list<string>
     */
    public function modelsForProvider(string $provider): array
    {
        return $this->providers()[$provider]['models'] ?? [];
    }

    /**
     * Retrieve or instantiate the singleton agent.
     */
    public function resolveAgent(): AiAgent
    {
        return AiAgent::first() ?? new AiAgent;
    }

    /**
     * Persist validated settings to the agent record.
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateAgent(array $validated): AiAgent
    {
        $agent = $this->resolveAgent();

        $agent->name = $validated['name'];
        $agent->description = $validated['description'];
        $agent->provider = $validated['provider'];
        $agent->model = $validated['model'];
        $agent->personality = $validated['personality'];
        $agent->status = $validated['status'];
        $agent->settings = $this->buildSettings($validated);

        if ($validated['status'] === 'active' && ! $agent->enabled_at) {
            $agent->enabled_at = now();
        }

        $agent->save();

        return $agent;
    }

    /**
     * Aggregate session statistics for the given agent.
     *
     * @return array<string, int|string|null>
     */
    public function statistics(AiAgent $agent): array
    {
        return [
            'total_sessions' => $agent->sessions()->count(),
            'active_sessions' => $agent->sessions()->where('status', 'active')->count(),
            'completed_sessions' => $agent->sessions()->where('status', 'completed')->count(),
            'failed_sessions' => $agent->sessions()->where('status', 'failed')->count(),
            'total_messages' => $agent->sessions()
                ->join('chat_ai_agent_session_messages', 'chat_ai_agent_sessions.id', 'chat_ai_agent_session_messages.session_id')
                ->count(),
            'average_session_duration' => $agent->sessions()
                ->whereNotNull('ended_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, ended_at)) as avg_duration')
                ->pluck('avg_duration')
                ->first() ?? 0,
            'enabled_at' => $agent->enabled_at?->format('Y-m-d H:i:s'),
            'status' => $agent->status,
        ];
    }

    /**
     * Test the API connection for the given provider configuration.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws \Exception
     */
    public function testConnection(array $config): void
    {
        match ($config['provider']) {
            'openai' => $this->testOpenAI($config),
            'anthropic' => $this->testAnthropic($config),
            'gemini' => $this->testGemini($config),
            'local' => $this->testLocal($config),
            default => throw new \Exception('Provider no soportado'),
        };
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Build the settings JSON array from validated input.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildSettings(array $validated): array
    {
        $settings = [
            'api_key' => $validated['api_key'] ?? null,
            'temperature' => (float) ($validated['temperature'] ?? 0.7),
            'max_tokens' => (int) ($validated['max_tokens'] ?? 2048),
            'top_p' => (float) ($validated['top_p'] ?? 1.0),
            'frequency_penalty' => (float) ($validated['frequency_penalty'] ?? 0),
            'presence_penalty' => (float) ($validated['presence_penalty'] ?? 0),
        ];

        return match ($validated['provider']) {
            'openai' => array_merge($settings, ['organization_id' => $validated['organization_id'] ?? null]),
            'anthropic' => array_merge($settings, ['version' => $validated['version'] ?? '2023-06-01']),
            'local' => array_merge($settings, ['base_url' => $validated['base_url'] ?? 'http://localhost:11434']),
            default => $settings,
        };
    }

    /** @param array<string, mixed> $config */
    private function testOpenAI(array $config): void
    {
        $apiKey = $config['api_key'] ?? setting('openai_api_key');

        if (! $apiKey) {
            throw new \Exception('API key no configurada para OpenAI');
        }

        $response = Http::withToken($apiKey)
            ->get('https://api.openai.com/v1/models/'.$config['model']);

        if (! $response->ok()) {
            throw new \Exception('Error al conectar con OpenAI: '.$response->body());
        }
    }

    /** @param array<string, mixed> $config */
    private function testAnthropic(array $config): void
    {
        $apiKey = $config['api_key'] ?? setting('anthropic_api_key');

        if (! $apiKey) {
            throw new \Exception('API key no configurada para Anthropic');
        }

        $response = Http::withHeader('x-api-key', $apiKey)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $config['model'],
                'max_tokens' => 100,
                'messages' => [['role' => 'user', 'content' => 'test']],
            ]);

        if (! $response->ok()) {
            throw new \Exception('Error al conectar con Anthropic: '.$response->body());
        }
    }

    /** @param array<string, mixed> $config */
    private function testGemini(array $config): void
    {
        $apiKey = $config['api_key'] ?? setting('gemini_api_key');

        if (! $apiKey) {
            throw new \Exception('API key no configurada para Gemini');
        }

        $response = Http::get(
            'https://generativelanguage.googleapis.com/v1/models/'.$config['model'],
            ['key' => $apiKey]
        );

        if (! $response->ok()) {
            throw new \Exception('Error al conectar con Gemini: '.$response->body());
        }
    }

    /** @param array<string, mixed> $config */
    private function testLocal(array $config): void
    {
        $baseUrl = $config['base_url'] ?? 'http://localhost:11434';
        $response = Http::get($baseUrl.'/api/tags');

        if (! $response->ok()) {
            throw new \Exception('No se pudo conectar con Ollama en '.$baseUrl);
        }

        $modelExists = collect($response->json('models', []))->contains('name', $config['model']);

        if (! $modelExists) {
            throw new \Exception('Modelo '.$config['model'].' no encontrado en Ollama');
        }
    }
}

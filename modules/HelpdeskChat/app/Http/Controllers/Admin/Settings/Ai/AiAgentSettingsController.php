<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Ai;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Ai\AiAgent;

class AiAgentSettingsController extends Controller
{
    /**
     * Show AI Agent settings form
     */
    public function index()
    {
        $this->authorize('viewAny', AiAgent::class);

        // Get or create default agent
        $agent = AiAgent::first() ?? new AiAgent;

        // Provider configurations
        $providers = [
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

        // Status options
        $statuses = [
            'inactive' => 'Inactivo',
            'active' => 'Activo',
            'paused' => 'En Pausa',
        ];

        return view('theme.views.helpdesk.ai-agent.settings', [
            'agent' => $agent,
            'providers' => $providers,
            'statuses' => $statuses,
            'hasAgent' => AiAgent::exists(),
        ]);
    }

    /**
     * Update AI Agent settings
     */
    public function update(Request $request)
    {
        $this->authorize('update', AiAgent::class);

        // Validate input
        $validated = $this->validateSettings($request);

        // Get or create agent
        $agent = AiAgent::first() ?? new AiAgent;

        // Update agent
        $agent->name = $validated['name'];
        $agent->description = $validated['description'];
        $agent->provider = $validated['provider'];
        $agent->model = $validated['model'];
        $agent->personality = $validated['personality'];
        $agent->status = $validated['status'];

        // Update settings (API keys, parameters)
        $settings = [
            'api_key' => $validated['api_key'] ?? null,
            'temperature' => (float) ($validated['temperature'] ?? 0.7),
            'max_tokens' => (int) ($validated['max_tokens'] ?? 2048),
            'top_p' => (float) ($validated['top_p'] ?? 1.0),
            'frequency_penalty' => (float) ($validated['frequency_penalty'] ?? 0),
            'presence_penalty' => (float) ($validated['presence_penalty'] ?? 0),
        ];

        // Add provider-specific settings
        if ($validated['provider'] === 'openai') {
            $settings['organization_id'] = $validated['organization_id'] ?? null;
        } elseif ($validated['provider'] === 'anthropic') {
            $settings['version'] = $validated['version'] ?? '2023-06-01';
        } elseif ($validated['provider'] === 'local') {
            $settings['base_url'] = $validated['base_url'] ?? 'http://localhost:11434';
        }

        $agent->settings = $settings;

        // Update status timestamp if status changed to active
        if ($validated['status'] === 'active' && ! $agent->enabled_at) {
            $agent->enabled_at = now();
        }

        $agent->save();

        return redirect()
            ->route('manager.helpdesk.ai.settings')
            ->with('success', 'Configuración del agente IA actualizada correctamente');
    }

    /**
     * Test API connection
     */
    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|in:openai,anthropic,gemini,local',
            'api_key' => 'nullable|string',
            'model' => 'required|string',
            'base_url' => 'nullable|url',
        ]);

        try {
            // Test connection based on provider
            match ($validated['provider']) {
                'openai' => $this->testOpenAIConnection($validated),
                'anthropic' => $this->testAnthropicConnection($validated),
                'gemini' => $this->testGeminiConnection($validated),
                'local' => $this->testLocalConnection($validated),
                default => throw new \Exception('Provider no soportado'),
            };

            return response()->json(['success' => true, 'message' => 'Conexión exitosa']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get available models for provider
     */
    public function getModels(Request $request)
    {
        $request->validate(['provider' => 'required|in:openai,anthropic,gemini,local']);

        $models = match ($request->provider) {
            'openai' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'],
            'anthropic' => ['claude-3-opus-20250219', 'claude-3-sonnet-20250229', 'claude-3-haiku-20250307'],
            'gemini' => ['gemini-2.0-flash', 'gemini-1.5-pro', 'gemini-1.5-flash'],
            'local' => ['llama2', 'mistral', 'neural-chat', 'openchat'],
            default => [],
        };

        return response()->json(['models' => $models]);
    }

    /**
     * Get agent statistics
     */
    public function statistics()
    {
        $agent = AiAgent::first();

        if (! $agent) {
            return response()->json(['error' => 'No agent configured'], 404);
        }

        $stats = [
            'total_sessions' => $agent->sessions()->count(),
            'active_sessions' => $agent->sessions()->where('status', 'active')->count(),
            'completed_sessions' => $agent->sessions()->where('status', 'completed')->count(),
            'failed_sessions' => $agent->sessions()->where('status', 'failed')->count(),
            'total_messages' => $agent->sessions()
                ->join('helpdesk_ai_agent_session_messages', 'helpdesk_ai_agent_sessions.id', 'helpdesk_ai_agent_session_messages.session_id')
                ->count(),
            'average_session_duration' => $agent->sessions()
                ->whereNotNull('ended_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, ended_at)) as avg_duration')
                ->pluck('avg_duration')
                ->first() ?? 0,
            'enabled_at' => $agent->enabled_at?->format('Y-m-d H:i:s'),
            'status' => $agent->status,
        ];

        return response()->json($stats);
    }

    // Private helper methods

    /**
     * Validate settings input
     */
    private function validateSettings(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'provider' => 'required|in:openai,anthropic,gemini,local',
            'model' => 'required|string|max:255',
            'personality' => 'required|string|max:10000',
            'status' => 'required|in:inactive,active,paused',
            'api_key' => 'nullable|string|max:500',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:128000',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'frequency_penalty' => 'nullable|numeric|min:-2|max:2',
            'presence_penalty' => 'nullable|numeric|min:-2|max:2',
            'organization_id' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:255',
            'base_url' => 'nullable|url',
        ]);
    }

    /**
     * Test OpenAI connection
     */
    private function testOpenAIConnection(array $config): void
    {
        $apiKey = $config['api_key'] ?? setting('openai_api_key');

        if (! $apiKey) {
            throw new \Exception('API key no configurada para OpenAI');
        }

        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->get('https://api.openai.com/v1/models/'.$config['model']);

        if (! $response->ok()) {
            throw new \Exception('Error al conectar con OpenAI: '.$response->body());
        }
    }

    /**
     * Test Anthropic connection
     */
    private function testAnthropicConnection(array $config): void
    {
        $apiKey = $config['api_key'] ?? setting('anthropic_api_key');

        if (! $apiKey) {
            throw new \Exception('API key no configurada para Anthropic');
        }

        $response = \Illuminate\Support\Facades\Http::withHeader('x-api-key', $apiKey)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $config['model'],
                'max_tokens' => 100,
                'messages' => [['role' => 'user', 'content' => 'test']],
            ]);

        if (! $response->ok()) {
            throw new \Exception('Error al conectar con Anthropic: '.$response->body());
        }
    }

    /**
     * Test Google Gemini connection
     */
    private function testGeminiConnection(array $config): void
    {
        $apiKey = $config['api_key'] ?? setting('gemini_api_key');

        if (! $apiKey) {
            throw new \Exception('API key no configurada para Gemini');
        }

        $response = \Illuminate\Support\Facades\Http::get(
            'https://generativelanguage.googleapis.com/v1/models/'.$config['model'],
            ['key' => $apiKey]
        );

        if (! $response->ok()) {
            throw new \Exception('Error al conectar con Gemini: '.$response->body());
        }
    }

    /**
     * Test local model connection (Ollama)
     */
    private function testLocalConnection(array $config): void
    {
        $baseUrl = $config['base_url'] ?? 'http://localhost:11434';

        $response = \Illuminate\Support\Facades\Http::get($baseUrl.'/api/tags');

        if (! $response->ok()) {
            throw new \Exception('No se pudo conectar con Ollama en '.$baseUrl);
        }

        $models = $response->json('models', []);
        $modelExists = collect($models)->contains('name', $config['model']);

        if (! $modelExists) {
            throw new \Exception('Modelo '.$config['model'].' no encontrado en Ollama');
        }
    }

    // ==================== TAGS MANAGEMENT ====================

    /**
     * Get all tags
     */
    public function tagsIndex()
    {
        $tags = \App\Models\Helpdesk\AiAgentTag::orderBy('priority', 'desc')->get();

        return view('theme.views.helpdesk.ai-agent.partials.tags-tab', compact('tags'));
    }

    /**
     * Store a new tag
     */
    public function tagsStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'icon' => 'nullable|string|max:100',
            'system_prompt_addition' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        \App\Models\Helpdesk\AiAgentTag::create($validated);

        return response()->json(['success' => true, 'message' => 'Tag creado correctamente']);
    }

    /**
     * Update a tag
     */
    public function tagsUpdate(Request $request, $id)
    {
        $tag = \App\Models\Helpdesk\AiAgentTag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'icon' => 'nullable|string|max:100',
            'system_prompt_addition' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $tag->update($validated);

        return response()->json(['success' => true, 'message' => 'Tag actualizado correctamente']);
    }

    /**
     * Delete a tag
     */
    public function tagsDestroy($id)
    {
        $tag = \App\Models\Helpdesk\AiAgentTag::findOrFail($id);
        $tag->delete();

        return response()->json(['success' => true, 'message' => 'Tag eliminado correctamente']);
    }

    /**
     * Toggle tag active status
     */
    public function tagsToggle(Request $request, $id)
    {
        $tag = \App\Models\Helpdesk\AiAgentTag::findOrFail($id);
        $tag->update(['is_active' => $request->is_active]);

        return response()->json(['success' => true]);
    }

    // ==================== TOOLS MANAGEMENT ====================

    /**
     * Get all tools
     */
    public function toolsIndex()
    {
        $agent = AiAgent::first();
        $tools = $agent ? $agent->tools()->orderBy('created_at', 'desc')->get() : collect();

        return view('theme.views.helpdesk.ai-agent.partials.tools-tab', compact('tools'));
    }

    /**
     * Store a new tool
     */
    public function toolsStore(Request $request)
    {
        $agent = AiAgent::first();
        if (! $agent) {
            return response()->json(['success' => false, 'message' => 'No hay agente configurado'], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:function,api,database,custom',
            'parameters' => 'nullable|array',
            'implementation' => 'nullable|string',
            'auth_config' => 'nullable|array',
            'requires_approval' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['ai_agent_id'] = $agent->id;
        $validated['requires_approval'] = $request->has('requires_approval');
        $validated['is_active'] = $request->has('is_active');

        \App\Models\Helpdesk\AiAgentTool::create($validated);

        return response()->json(['success' => true, 'message' => 'Herramienta creada correctamente']);
    }

    /**
     * Update a tool
     */
    public function toolsUpdate(Request $request, $id)
    {
        $tool = \App\Models\Helpdesk\AiAgentTool::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:function,api,database,custom',
            'parameters' => 'nullable|array',
            'implementation' => 'nullable|string',
            'auth_config' => 'nullable|array',
            'requires_approval' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['requires_approval'] = $request->has('requires_approval');
        $validated['is_active'] = $request->has('is_active');

        $tool->update($validated);

        return response()->json(['success' => true, 'message' => 'Herramienta actualizada correctamente']);
    }

    /**
     * Delete a tool
     */
    public function toolsDestroy($id)
    {
        $tool = \App\Models\Helpdesk\AiAgentTool::findOrFail($id);
        $tool->delete();

        return response()->json(['success' => true, 'message' => 'Herramienta eliminada correctamente']);
    }

    /**
     * Toggle tool active status
     */
    public function toolsToggle(Request $request, $id)
    {
        $tool = \App\Models\Helpdesk\AiAgentTool::findOrFail($id);
        $tool->update(['is_active' => $request->is_active]);

        return response()->json(['success' => true]);
    }

    // ==================== KNOWLEDGE BASE MANAGEMENT ====================

    /**
     * Get all knowledge base entries
     */
    public function knowledgeIndex()
    {
        $agent = AiAgent::first();
        $knowledge = $agent ? $agent->knowledgeBase()->orderBy('created_at', 'desc')->get() : collect();

        return view('theme.views.helpdesk.ai-agent.partials.knowledge-tab', compact('knowledge'));
    }

    /**
     * Store a new knowledge entry
     */
    public function knowledgeStore(Request $request)
    {
        $agent = AiAgent::first();
        if (! $agent) {
            return response()->json(['success' => false, 'message' => 'No hay agente configurado'], 400);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:document,faq,article,manual,url',
            'source_url' => 'nullable|url',
            'source_type' => 'nullable|string',
            'tags' => 'nullable|array',
            'summary' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['ai_agent_id'] = $agent->id;
        $validated['is_active'] = $request->has('is_active');

        \App\Models\Helpdesk\AiAgentKnowledgeBase::create($validated);

        return response()->json(['success' => true, 'message' => 'Documento creado correctamente']);
    }

    /**
     * Update a knowledge entry
     */
    public function knowledgeUpdate(Request $request, $id)
    {
        $knowledge = \App\Models\Helpdesk\AiAgentKnowledgeBase::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:document,faq,article,manual,url',
            'source_url' => 'nullable|url',
            'source_type' => 'nullable|string',
            'tags' => 'nullable|array',
            'summary' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $knowledge->update($validated);

        return response()->json(['success' => true, 'message' => 'Documento actualizado correctamente']);
    }

    /**
     * Delete a knowledge entry
     */
    public function knowledgeDestroy($id)
    {
        $knowledge = \App\Models\Helpdesk\AiAgentKnowledgeBase::findOrFail($id);
        $knowledge->delete();

        return response()->json(['success' => true, 'message' => 'Documento eliminado correctamente']);
    }

    /**
     * Toggle knowledge active status
     */
    public function knowledgeToggle(Request $request, $id)
    {
        $knowledge = \App\Models\Helpdesk\AiAgentKnowledgeBase::findOrFail($id);
        $knowledge->update(['is_active' => $request->is_active]);

        return response()->json(['success' => true]);
    }

    /**
     * Generate embedding for knowledge entry
     */
    public function knowledgeGenerateEmbedding($id)
    {
        $knowledge = \App\Models\Helpdesk\AiAgentKnowledgeBase::findOrFail($id);

        // This would call the actual embedding API
        // For now, just mark it as processed
        $knowledge->generateEmbedding();

        return response()->json(['success' => true, 'message' => 'Embedding generado correctamente']);
    }
}

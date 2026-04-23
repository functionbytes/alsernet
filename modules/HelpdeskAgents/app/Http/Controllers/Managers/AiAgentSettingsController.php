<?php

namespace Modules\HelpdeskAgents\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentKnowledgeBase;
use Modules\HelpdeskAgents\Models\AiAgentTag;
use Modules\HelpdeskAgents\Models\AiAgentTool;

class AiAgentSettingsController extends Controller
{
    /**
     * Show AI Agent backups form
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
                'icon' => 'fa-duotone 🟢',
            ],
            'anthropic' => [
                'label' => 'Anthropic (Claude)',
                'models' => ['claude-3-opus-20250219', 'claude-3-sonnet-20250229', 'claude-3-haiku-20250307'],
                'icon' => 'fa-duotone 🔵',
            ],
            'gemini' => [
                'label' => 'Google Gemini',
                'models' => ['gemini-2.0-flash', 'gemini-1.5-pro', 'gemini-1.5-flash'],
                'icon' => 'fa-duotone 🟡',
            ],
            'local' => [
                'label' => 'Local Model (Ollama)',
                'models' => ['llama2', 'mistral', 'neural-chat', 'openchat'],
                'icon' => 'fa-duotone ⚪',
            ],
        ];

        // Status options
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

    /**
     * Update AI Agent backups
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

        // Store API key in the dedicated encrypted column (never in backups JSON).
        if (filled($validated['api_key'] ?? null)) {
            $agent->api_key_encrypted = $validated['api_key'];
        }

        // All other model parameters live in the backups JSON column.
        $backups = [
            'temperature' => (float) ($validated['temperature'] ?? 0.7),
            'max_tokens' => (int) ($validated['max_tokens'] ?? 2048),
            'top_p' => (float) ($validated['top_p'] ?? 1.0),
            'frequency_penalty' => (float) ($validated['frequency_penalty'] ?? 0),
            'presence_penalty' => (float) ($validated['presence_penalty'] ?? 0),
        ];

        // Provider-specific parameters
        if ($validated['provider'] === 'openai') {
            $backups['organization_id'] = $validated['organization_id'] ?? null;
        } elseif ($validated['provider'] === 'anthropic') {
            $backups['version'] = $validated['version'] ?? '2023-06-01';
        } elseif ($validated['provider'] === 'local') {
            $backups['base_url'] = $validated['base_url'] ?? 'http://localhost:11434';
        }

        $agent->backups = $backups;

        // Update status timestamp if status changed to active
        if ($validated['status'] === 'active' && ! $agent->enabled_at) {
            $agent->enabled_at = now();
        }

        $agent->save();

        Cache::forget('helpdesk:ai-agent:first');

        return redirect()
            ->route('manager.helpdesk.ai.settings')
            ->with('success', 'Configuración del agente IA actualizada correctamente');
    }

    /**
     * Test API connection
     */
    public function testConnection(Request $request)
    {
        $this->authorize('manage', AiAgent::class);

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
                'message' => 'Error de conexión. Por favor, inténtalo de nuevo.',
            ], 400);
        }
    }

    /**
     * Get available models for provider
     */
    public function getModels(Request $request)
    {
        $this->authorize('viewAny', AiAgent::class);

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
        $this->authorize('viewAny', AiAgent::class);

        $agent = AiAgent::first();

        if (! $agent) {
            return response()->json(['error' => 'No agent configured'], 404);
        }

        // Single grouped query instead of 5 separate COUNTs
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

        $stats = [
            'total_sessions' => (int) ($sessionCounts->total ?? 0),
            'active_sessions' => (int) ($sessionCounts->active ?? 0),
            'completed_sessions' => (int) ($sessionCounts->completed ?? 0),
            'failed_sessions' => (int) ($sessionCounts->failed ?? 0),
            'total_messages' => $totalMessages,
            'average_session_duration' => round((float) ($sessionCounts->avg_duration ?? 0), 1),
            'enabled_at' => $agent->enabled_at?->format('Y-m-d H:i:s'),
            'status' => $agent->status,
        ];

        return response()->json($stats);
    }

    // Private helper methods

    /**
     * Validate backups input
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

        $response = Http::withToken($apiKey)
            ->timeout(10)
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

        $response = Http::withHeader('x-api-key', $apiKey)
            ->timeout(10)
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

        $response = Http::timeout(10)->get(
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

        $response = Http::timeout(10)->get($baseUrl.'/api/tags');

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
        $this->authorize('viewAny', AiAgent::class);

        $tags = AiAgentTag::orderBy('priority', 'desc')->paginate(50);

        return view('helpdeskagents::managers.ai-agent.partials.tags-tab', compact('tags'));
    }

    /**
     * Store a new tag
     */
    public function tagsStore(Request $request)
    {
        $this->authorize('create', AiAgent::class);

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

        AiAgentTag::create($validated);

        return response()->json(['success' => true, 'message' => 'Tag creado correctamente']);
    }

    /**
     * Update a tag
     */
    public function tagsUpdate(Request $request, $id)
    {
        $this->authorize('update', AiAgent::class);

        $tag = AiAgentTag::findOrFail($id);

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
        $this->authorize('delete', AiAgent::class);

        $tag = AiAgentTag::findOrFail($id);
        $tag->delete();

        return response()->json(['success' => true, 'message' => 'Tag eliminado correctamente']);
    }

    /**
     * Toggle tag active status
     */
    public function tagsToggle(Request $request, $id)
    {
        $this->authorize('update', AiAgent::class);

        $tag = AiAgentTag::findOrFail($id);
        $tag->update(['is_active' => $request->is_active]);

        return response()->json(['success' => true]);
    }

    // ==================== TOOLS MANAGEMENT ====================

    /**
     * Get all tools
     */
    public function toolsIndex()
    {
        $this->authorize('viewAny', AiAgent::class);

        $agent = AiAgent::first();
        $tools = $agent ? $agent->tools()->orderBy('created_at', 'desc')->paginate(50) : new LengthAwarePaginator([], 0, 50);

        return view('helpdeskagents::managers.ai-agent.partials.tools-tab', compact('tools'));
    }

    /**
     * Store a new tool
     */
    public function toolsStore(Request $request)
    {
        $this->authorize('create', AiAgent::class);

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

        AiAgentTool::create($validated);

        return response()->json(['success' => true, 'message' => 'Herramienta creada correctamente']);
    }

    /**
     * Update a tool
     */
    public function toolsUpdate(Request $request, $id)
    {
        $this->authorize('update', AiAgent::class);

        $tool = AiAgentTool::findOrFail($id);

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
        $this->authorize('delete', AiAgent::class);

        $tool = AiAgentTool::findOrFail($id);
        $tool->delete();

        return response()->json(['success' => true, 'message' => 'Herramienta eliminada correctamente']);
    }

    /**
     * Toggle tool active status
     */
    public function toolsToggle(Request $request, $id)
    {
        $this->authorize('update', AiAgent::class);

        $tool = AiAgentTool::findOrFail($id);
        $tool->update(['is_active' => $request->is_active]);

        return response()->json(['success' => true]);
    }

    // ==================== KNOWLEDGE BASE MANAGEMENT ====================

    /**
     * Get all knowledge base entries
     */
    public function knowledgeIndex()
    {
        $this->authorize('viewAny', AiAgent::class);

        $agent = AiAgent::first();
        $knowledge = $agent ? $agent->knowledgeBase()->orderBy('created_at', 'desc')->paginate(50) : new LengthAwarePaginator([], 0, 50);

        return view('helpdeskagents::managers.ai-agent.partials.knowledge-tab', compact('knowledge'));
    }

    /**
     * Store a new knowledge entry
     */
    public function knowledgeStore(Request $request)
    {
        $this->authorize('create', AiAgent::class);

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

        AiAgentKnowledgeBase::create($validated);

        return response()->json(['success' => true, 'message' => 'Documento creado correctamente']);
    }

    /**
     * Update a knowledge entry
     */
    public function knowledgeUpdate(Request $request, $id)
    {
        $this->authorize('update', AiAgent::class);

        $knowledge = AiAgentKnowledgeBase::findOrFail($id);

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
        $this->authorize('delete', AiAgent::class);

        $knowledge = AiAgentKnowledgeBase::findOrFail($id);
        $knowledge->delete();

        return response()->json(['success' => true, 'message' => 'Documento eliminado correctamente']);
    }

    /**
     * Toggle knowledge active status
     */
    public function knowledgeToggle(Request $request, $id)
    {
        $this->authorize('update', AiAgent::class);

        $knowledge = AiAgentKnowledgeBase::findOrFail($id);
        $knowledge->update(['is_active' => $request->is_active]);

        return response()->json(['success' => true]);
    }

    /**
     * Generate embedding for knowledge entry
     */
    public function knowledgeGenerateEmbedding($id)
    {
        $this->authorize('update', AiAgent::class);

        $knowledge = AiAgentKnowledgeBase::findOrFail($id);

        // This would call the actual embedding API
        // For now, just mark it as processed
        $knowledge->generateEmbedding();

        return response()->json(['success' => true, 'message' => 'Embedding generado correctamente']);
    }
}

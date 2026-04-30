<?php

namespace Modules\Chat\Http\Controllers\Settings\Ai;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Ai\AiAgent;
use Modules\Chat\Models\Ai\AiAgentKnowledgeBase;
use Modules\Chat\Models\Ai\AiAgentTag;
use Modules\Chat\Models\Ai\AiAgentTool;
use Modules\Chat\Services\Ai\AiAgentConfigService;
use Modules\Chat\Services\Ai\AiAgentKnowledgeService;
use Modules\Chat\Services\Ai\AiAgentTagService;

class AiAgentSettingsController extends Controller
{
    public function __construct(
        private readonly AiAgentConfigService $config,
        private readonly AiAgentTagService $tags,
        private readonly AiAgentKnowledgeService $knowledge,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // Agent configuration
    // ──────────────────────────────────────────────────────────────

    public function index(): View
    {
        $this->authorize('viewAny', AiAgent::class);

        return view('Chat::chats.ai-agent.settings', [
            'agent' => $this->config->resolveAgent(),
            'providers' => $this->config->providers(),
            'statuses' => $this->config->statuses(),
            'hasAgent' => AiAgent::exists(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', AiAgent::class);

        $validated = $request->validate([
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

        $this->config->updateAgent($validated);

        return redirect()
            ->route('manager.chat.ai.settings')
            ->with('success', 'Configuración del agente IA actualizada correctamente');
    }

    public function testConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|in:openai,anthropic,gemini,local',
            'api_key' => 'nullable|string',
            'model' => 'required|string',
            'base_url' => 'nullable|url',
        ]);

        try {
            $this->config->testConnection($validated);

            return response()->json(['success' => true, 'message' => 'Conexión exitosa']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: '.$e->getMessage(),
            ], 400);
        }
    }

    public function getModels(Request $request): JsonResponse
    {
        $request->validate(['provider' => 'required|in:openai,anthropic,gemini,local']);

        return response()->json([
            'models' => $this->config->modelsForProvider($request->provider),
        ]);
    }

    public function statistics(): JsonResponse
    {
        $agent = AiAgent::first();

        if (! $agent) {
            return response()->json(['error' => 'No agent configured'], 404);
        }

        return response()->json($this->config->statistics($agent));
    }

    // ──────────────────────────────────────────────────────────────
    // Tags
    // ──────────────────────────────────────────────────────────────

    public function tagsIndex(): View
    {
        $tags = $this->tags->all();

        return view('Chat::chats.ai-agent.partials.tags-tab', compact('tags'));
    }

    public function tagsStore(Request $request): JsonResponse
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

        $this->tags->create($validated);

        return response()->json(['success' => true, 'message' => 'Tag creado correctamente']);
    }

    public function tagsUpdate(Request $request, int $id): JsonResponse
    {
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

        $this->tags->update($tag, $validated);

        return response()->json(['success' => true, 'message' => 'Tag actualizado correctamente']);
    }

    public function tagsDestroy(int $id): JsonResponse
    {
        $this->tags->delete(AiAgentTag::findOrFail($id));

        return response()->json(['success' => true, 'message' => 'Tag eliminado correctamente']);
    }

    public function tagsToggle(Request $request, int $id): JsonResponse
    {
        $this->tags->toggle(AiAgentTag::findOrFail($id), (bool) $request->is_active);

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────
    // Tools
    // ──────────────────────────────────────────────────────────────

    public function toolsIndex(): View
    {
        $agent = AiAgent::first();
        $tools = $agent ? $this->knowledge->allTools($agent) : collect();

        return view('Chat::chats.ai-agent.partials.tools-tab', compact('tools'));
    }

    public function toolsStore(Request $request): JsonResponse
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

        $validated['requires_approval'] = $request->has('requires_approval');
        $validated['is_active'] = $request->has('is_active');

        $this->knowledge->createTool($agent, $validated);

        return response()->json(['success' => true, 'message' => 'Herramienta creada correctamente']);
    }

    public function toolsUpdate(Request $request, int $id): JsonResponse
    {
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

        $this->knowledge->updateTool($tool, $validated);

        return response()->json(['success' => true, 'message' => 'Herramienta actualizada correctamente']);
    }

    public function toolsDestroy(int $id): JsonResponse
    {
        $this->knowledge->deleteTool(AiAgentTool::findOrFail($id));

        return response()->json(['success' => true, 'message' => 'Herramienta eliminada correctamente']);
    }

    public function toolsToggle(Request $request, int $id): JsonResponse
    {
        $this->knowledge->toggleTool(AiAgentTool::findOrFail($id), (bool) $request->is_active);

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────
    // Knowledge base
    // ──────────────────────────────────────────────────────────────

    public function knowledgeIndex(): View
    {
        $agent = AiAgent::first();
        $knowledge = $agent ? $this->knowledge->allKnowledge($agent) : collect();

        return view('Chat::chats.ai-agent.partials.knowledge-tab', compact('knowledge'));
    }

    public function knowledgeStore(Request $request): JsonResponse
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

        $validated['is_active'] = $request->has('is_active');

        $this->knowledge->createKnowledge($agent, $validated);

        return response()->json(['success' => true, 'message' => 'Documento creado correctamente']);
    }

    public function knowledgeUpdate(Request $request, int $id): JsonResponse
    {
        $entry = AiAgentKnowledgeBase::findOrFail($id);

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

        $this->knowledge->updateKnowledge($entry, $validated);

        return response()->json(['success' => true, 'message' => 'Documento actualizado correctamente']);
    }

    public function knowledgeDestroy(int $id): JsonResponse
    {
        $this->knowledge->deleteKnowledge(AiAgentKnowledgeBase::findOrFail($id));

        return response()->json(['success' => true, 'message' => 'Documento eliminado correctamente']);
    }

    public function knowledgeToggle(Request $request, int $id): JsonResponse
    {
        $this->knowledge->toggleKnowledge(AiAgentKnowledgeBase::findOrFail($id), (bool) $request->is_active);

        return response()->json(['success' => true]);
    }

    public function knowledgeGenerateEmbedding(int $id): JsonResponse
    {
        $this->knowledge->generateEmbedding(AiAgentKnowledgeBase::findOrFail($id));

        return response()->json(['success' => true, 'message' => 'Embedding generado correctamente']);
    }
}

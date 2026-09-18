<?php

namespace Modules\HelpdeskAgents\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Modules\HelpdeskAgents\Concerns\InteractsWithDefaultAiAgent;
use Modules\HelpdeskAgents\Http\Requests\StoreAiAgentKnowledgeRequest;
use Modules\HelpdeskAgents\Http\Requests\UpdateAiAgentKnowledgeRequest;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentKnowledgeBase;

class AiKnowledgeController extends Controller
{
    use InteractsWithDefaultAiAgent;

    public function index(): View
    {
        $this->authorize('viewAny', AiAgent::class);

        $agent = $this->getDefaultAgent();
        $knowledge = $agent
            ? $agent->knowledgeBase()->orderBy('created_at', 'desc')->paginate(50)
            : new LengthAwarePaginator([], 0, 50);

        return view('helpdeskagents::managers.ai-agent.partials.knowledge-tab', compact('knowledge'));
    }

    public function store(StoreAiAgentKnowledgeRequest $request): JsonResponse
    {
        $this->authorize('create', AiAgent::class);

        $agent = $this->getDefaultAgent();

        if (! $agent) {
            return response()->json(['success' => false, 'message' => 'No hay agente configurado'], 400);
        }

        $validated = $request->validated();
        $validated['ai_agent_id'] = $agent->id;
        $validated['is_active'] = $request->has('is_active');

        AiAgentKnowledgeBase::create($validated);

        return response()->json(['success' => true, 'message' => 'Documento creado correctamente']);
    }

    public function update(UpdateAiAgentKnowledgeRequest $request, AiAgentKnowledgeBase $knowledge): JsonResponse
    {
        $this->authorize('update', AiAgent::class);

        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');
        $knowledge->update($validated);

        return response()->json(['success' => true, 'message' => 'Documento actualizado correctamente']);
    }

    public function destroy(AiAgentKnowledgeBase $knowledge): JsonResponse
    {
        $this->authorize('delete', AiAgent::class);

        $knowledge->delete();

        return response()->json(['success' => true, 'message' => 'Documento eliminado correctamente']);
    }

    public function toggle(Request $request, AiAgentKnowledgeBase $knowledge): JsonResponse
    {
        $this->authorize('update', AiAgent::class);

        $knowledge->update(['is_active' => $request->boolean('is_active')]);

        return response()->json(['success' => true]);
    }

    public function generateEmbedding(AiAgentKnowledgeBase $knowledge): JsonResponse
    {
        $this->authorize('update', AiAgent::class);

        $knowledge->generateEmbedding();

        return response()->json(['success' => true, 'message' => 'Embedding generado correctamente']);
    }
}

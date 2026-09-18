<?php

namespace Modules\HelpdeskAgents\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Modules\HelpdeskAgents\Concerns\InteractsWithDefaultAiAgent;
use Modules\HelpdeskAgents\Http\Requests\StoreAiAgentToolRequest;
use Modules\HelpdeskAgents\Http\Requests\UpdateAiAgentToolRequest;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentTool;

class AiToolsController extends Controller
{
    use InteractsWithDefaultAiAgent;

    public function index(): View
    {
        $this->authorize('viewAny', AiAgent::class);

        $agent = $this->getDefaultAgent();
        $tools = $agent
            ? $agent->tools()->orderBy('created_at', 'desc')->paginate(50)
            : new LengthAwarePaginator([], 0, 50);

        return view('helpdeskagents::managers.ai-agent.partials.tools-tab', compact('tools'));
    }

    public function store(StoreAiAgentToolRequest $request): JsonResponse
    {
        $this->authorize('create', AiAgent::class);

        $agent = $this->getDefaultAgent();

        if (! $agent) {
            return response()->json(['success' => false, 'message' => 'No hay agente configurado'], 400);
        }

        $validated = $request->validated();
        $validated['ai_agent_id'] = $agent->id;
        $validated['requires_approval'] = $request->has('requires_approval');
        $validated['is_active'] = $request->has('is_active');

        AiAgentTool::create($validated);

        return response()->json(['success' => true, 'message' => 'Herramienta creada correctamente']);
    }

    public function update(UpdateAiAgentToolRequest $request, AiAgentTool $tool): JsonResponse
    {
        $this->authorize('update', AiAgent::class);

        $validated = $request->validated();
        $validated['requires_approval'] = $request->boolean('requires_approval');
        $validated['is_active'] = $request->boolean('is_active');
        $tool->update($validated);

        return response()->json(['success' => true, 'message' => 'Herramienta actualizada correctamente']);
    }

    public function destroy(AiAgentTool $tool): JsonResponse
    {
        $this->authorize('delete', AiAgent::class);

        $tool->delete();

        return response()->json(['success' => true, 'message' => 'Herramienta eliminada correctamente']);
    }

    public function toggle(Request $request, AiAgentTool $tool): JsonResponse
    {
        $this->authorize('update', AiAgent::class);

        $tool->update(['is_active' => $request->boolean('is_active')]);

        return response()->json(['success' => true]);
    }
}

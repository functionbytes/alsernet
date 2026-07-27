<?php

namespace Modules\HelpdeskAgents\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskAgents\Http\Requests\StoreAiAgentFlowRequest;
use Modules\HelpdeskAgents\Http\Requests\UpdateAiAgentFlowRequest;
use Modules\HelpdeskAgents\Http\Requests\UpdateAiAgentFlowStructureRequest;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentFlow;

class AiAgentFlowsController extends Controller
{
    /**
     * List flows for an agent
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', AiAgentFlow::class);

        abort_if(! helpdesk_agents_enabled(), 404);

        $agent = Cache::remember(
            'helpdesk:ai-agent:first',
            300,
            fn () => AiAgent::select(['id', 'name'])->oldest()->first()
        );

        if (! $agent) {
            return redirect()
                ->route('helpdesk.ai.settings')
                ->with('error', 'Primero debes configurar un agente IA');
        }

        $flows = $agent->flows()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->trigger, fn ($q) => $q->where('trigger', $request->trigger))
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->latest()
            ->paginate(20);

        $triggers = ['message' => 'Mensaje', 'intent' => 'Intención', 'keyword' => 'Palabra clave', 'conversation_start' => 'Inicio'];
        $statuses = ['draft' => 'Borrador', 'published' => 'Publicado', 'archived' => 'Archivado'];

        $rawStats = $agent->flows()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $stats = [
            'published' => $rawStats['published'] ?? 0,
            'draft' => $rawStats['draft'] ?? 0,
            'archived' => $rawStats['archived'] ?? 0,
            'total' => array_sum($rawStats),
        ];

        return view('helpdeskagents::managers.ai-agent.flows.index', [
            'flows' => $flows,
            'agent' => $agent,
            'triggers' => $triggers,
            'statuses' => $statuses,
            'stats' => $stats,
            'filters' => $request->only(['status', 'trigger', 'search']),
        ]);
    }

    /**
     * Create new flow
     */
    public function create()
    {
        $this->authorize('create', AiAgentFlow::class);

        $agent = Cache::remember(
            'helpdesk:ai-agent:first',
            300,
            fn () => AiAgent::select(['id', 'name'])->oldest()->first()
        );

        if (! $agent) {
            return redirect()->route('helpdesk.ai.settings');
        }

        $triggers = ['message' => 'Mensaje', 'intent' => 'Intención', 'keyword' => 'Palabra clave', 'conversation_start' => 'Inicio'];

        return view('helpdeskagents::managers.ai-agent.flows.create', [
            'agent' => $agent,
            'triggers' => $triggers,
        ]);
    }

    /**
     * Store new flow
     */
    public function store(StoreAiAgentFlowRequest $request)
    {
        $this->authorize('create', AiAgentFlow::class);

        $agent = AiAgent::first();

        if (! $agent) {
            return redirect()
                ->route('helpdesk.ai.flows.index')
                ->with('error', 'Configura primero un agente de IA antes de crear flujos.');
        }

        $validated = $request->validated();

        $flow = $agent->flows()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'trigger' => $validated['trigger'],
            'status' => 'draft',
            'nodes' => [],
            'edges' => [],
        ]);

        return redirect()
            ->route('helpdesk.ai.flows.edit', $flow)
            ->with('success', 'Flujo creado. Ahora puedes diseñar los nodos.');
    }

    /**
     * Edit flow with visual editor
     */
    public function edit(AiAgentFlow $flow)
    {
        $this->authorize('update', $flow);

        $nodeTypes = [
            'input' => 'Entrada',
            'prompt' => 'Prompt',
            'condition' => 'Condición',
            'action' => 'Acción',
            'output' => 'Salida',
        ];

        return view('helpdeskagents::managers.ai-agent.flows.edit', [
            'flow' => $flow,
            'agent' => $flow->agent,
            'nodeTypes' => $nodeTypes,
        ]);
    }

    /**
     * Update flow (general info)
     */
    public function update(UpdateAiAgentFlowRequest $request, AiAgentFlow $flow)
    {
        $this->authorize('update', $flow);

        $validated = $request->validated();

        $flow->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'trigger' => $validated['trigger'],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Flujo actualizado correctamente');
    }

    /**
     * Update flow structure (nodes & edges)
     */
    public function updateStructure(UpdateAiAgentFlowStructureRequest $request, AiAgentFlow $flow)
    {
        $this->authorize('update', $flow);

        $validated = $request->validated();

        $flow->update([
            'nodes' => $validated['nodes'],
            'edges' => $validated['edges'],
        ]);

        return response()->json(['success' => true, 'message' => 'Estructura del flujo guardada']);
    }

    /**
     * Publish flow
     */
    public function publish(AiAgentFlow $flow)
    {
        $this->authorize('update', $flow);

        // Validate flow has at least one node
        if (empty($flow->nodes)) {
            return redirect()
                ->back()
                ->with('error', 'El flujo debe tener al menos un nodo antes de publicar');
        }

        $flow->publish();

        return redirect()
            ->back()
            ->with('success', 'Flujo publicado correctamente');
    }

    /**
     * Archive flow
     */
    public function archive(AiAgentFlow $flow)
    {
        $this->authorize('update', $flow);

        $flow->archive();

        return redirect()
            ->back()
            ->with('success', 'Flujo archivado');
    }

    /**
     * Duplicate flow
     */
    public function duplicate(AiAgentFlow $flow)
    {
        $this->authorize('create', AiAgentFlow::class);

        $newFlow = $flow->replicate();
        $newFlow->name = $flow->name.' (Copia)';
        $newFlow->status = 'draft';
        $newFlow->published_at = null;
        $newFlow->save();

        return redirect()
            ->route('helpdesk.ai.flows.edit', $newFlow)
            ->with('success', 'Flujo duplicado correctamente');
    }

    /**
     * Delete flow
     */
    public function destroy(AiAgentFlow $flow)
    {
        $this->authorize('delete', $flow);

        $flow->delete();

        return redirect()
            ->route('helpdesk.ai.flows.index')
            ->with('success', 'Flujo eliminado correctamente');
    }
}

<?php

namespace Modules\HelpdeskAgents\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentFlow;
use Modules\HelpdeskAgents\Models\AiAgentFlowNode;

class AiAgentFlowsController extends Controller
{
    /**
     * List flows for an agent
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', AiAgentFlow::class);

        $agent = Cache::remember(
            'helpdesk:ai-agent:first',
            300,
            fn () => AiAgent::select(['id', 'name'])->oldest()->first()
        );

        if (! $agent) {
            return redirect()
                ->route('manager.helpdesk.ai.settings')
                ->with('error', 'Primero debes configurar un agente IA');
        }

        $flows = $agent->flows()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->trigger_type, fn ($q) => $q->where('trigger_type', $request->trigger_type))
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
            'filters' => $request->only(['status', 'trigger_type', 'search']),
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
            return redirect()->route('manager.helpdesk.ai.settings');
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
    public function store(Request $request)
    {
        $this->authorize('create', AiAgentFlow::class);

        $agent = AiAgent::first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|in:message,intent,keyword,conversation_start',
        ]);

        $flow = $agent->flows()->create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'trigger_type' => $validated['trigger_type'],
            'status' => 'draft',
            'nodes' => [],
            'edges' => [],
        ]);

        return redirect()
            ->route('manager.helpdesk.ai.flows.edit', $flow)
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
    public function update(Request $request, AiAgentFlow $flow)
    {
        $this->authorize('update', $flow);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|in:message,intent,keyword,conversation_start',
        ]);

        $flow->update($validated);

        return redirect()
            ->back()
            ->with('success', 'Flujo actualizado correctamente');
    }

    /**
     * Update flow structure (nodes & edges)
     */
    public function updateStructure(Request $request, AiAgentFlow $flow)
    {
        $this->authorize('update', $flow);

        $validated = $request->validate([
            'nodes' => 'present|array',
            'edges' => 'present|array',
        ]);

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
            ->route('manager.helpdesk.ai.flows.edit', $newFlow)
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
            ->route('manager.helpdesk.ai.flows.index')
            ->with('success', 'Flujo eliminado correctamente');
    }

    /**
     * Create a node in a flow
     */
    public function storeNode(Request $request, AiAgentFlow $flow)
    {
        $this->authorize('update', $flow);

        $validated = $request->validate([
            'node_id' => 'required|string',
            'type' => 'required|in:input,prompt,condition,action,output',
            'label' => 'required|string|max:255',
            'data' => 'nullable|array',
            'position' => 'required|array',
            'position.x' => 'required|numeric',
            'position.y' => 'required|numeric',
        ]);

        $node = $flow->flowNodes()->create([
            'node_id' => $validated['node_id'],
            'type' => $validated['type'],
            'label' => $validated['label'],
            'data' => $validated['data'] ?? [],
            'position' => $validated['position'],
        ]);

        return response()->json(['success' => true, 'node' => $node]);
    }

    /**
     * Update a node
     */
    public function updateNode(Request $request, AiAgentFlow $flow, AiAgentFlowNode $node)
    {
        $this->authorize('update', $flow);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'data' => 'nullable|array',
            'position' => 'required|array',
        ]);

        $node->update($validated);

        return response()->json(['success' => true, 'node' => $node]);
    }

    /**
     * Delete a node
     */
    public function deleteNode(AiAgentFlow $flow, AiAgentFlowNode $node)
    {
        $this->authorize('update', $flow);

        $node->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get flow with nodes and edges
     */
    public function show(AiAgentFlow $flow)
    {
        $this->authorize('view', $flow);

        return response()->json([
            'flow' => $flow,
            'nodes' => $flow->nodes,
            'edges' => $flow->edges,
            'flowNodes' => $flow->flowNodes,
        ]);
    }
}

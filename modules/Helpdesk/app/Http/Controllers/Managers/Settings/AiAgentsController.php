<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreAiAgentRequest;
use Modules\Helpdesk\Http\Requests\Settings\UpdateAiAgentRequest;
use Modules\Helpdesk\Models\AiAgent;

class AiAgentsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.ai-agents.view')->only(['index']);
        $this->middleware('can:helpdesk.ai-agents.manage')->only(['create', 'store', 'edit', 'update', 'destroy', 'toggle']);
    }

    public function index(Request $request): View
    {
        $query = AiAgent::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $aiAgents = $query->latest()->paginate(15);

        $statsRow = AiAgent::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN is_active = 0 OR is_active IS NULL THEN 1 ELSE 0 END) as inactive_count
        ')->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'active' => (int) $statsRow->active_count,
            'inactive' => (int) $statsRow->inactive_count,
        ];

        return view('helpdesk::settings.ai-agents.index', compact('aiAgents', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.ai-agents.create');
    }

    public function store(StoreAiAgentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['escalation_keywords'] = array_values(array_filter(
            explode("\n", str_replace("\r", '', $request->input('escalation_keywords', '')))
        ));
        $data['knowledge_sources'] = json_decode($request->input('knowledge_sources', '[]'), true) ?? [];
        $data['enabled_channels'] = $request->input('enabled_channels', []);

        AiAgent::create($data);

        return redirect()
            ->route('settings.helpdesk.ai-agents.index')
            ->with('success', 'Agente IA creado exitosamente.');
    }

    public function edit(AiAgent $aiAgent): View
    {
        return view('helpdesk::settings.ai-agents.edit', compact('aiAgent'));
    }

    public function update(UpdateAiAgentRequest $request, AiAgent $aiAgent): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['escalation_keywords'] = array_values(array_filter(
            explode("\n", str_replace("\r", '', $request->input('escalation_keywords', '')))
        ));
        $data['knowledge_sources'] = json_decode($request->input('knowledge_sources', '[]'), true) ?? [];
        $data['enabled_channels'] = $request->input('enabled_channels', []);

        $aiAgent->update($data);

        return redirect()
            ->route('settings.helpdesk.ai-agents.index')
            ->with('success', 'Agente IA actualizado exitosamente.');
    }

    public function destroy(AiAgent $aiAgent): RedirectResponse
    {
        $aiAgent->delete();

        return redirect()
            ->route('settings.helpdesk.ai-agents.index')
            ->with('success', 'Agente IA eliminado exitosamente.');
    }

    public function toggle(AiAgent $aiAgent): JsonResponse
    {
        $aiAgent->update(['is_active' => ! $aiAgent->is_active]);

        $status = $aiAgent->is_active ? 'activado' : 'desactivado';

        return response()->json([
            'success' => true,
            'is_active' => $aiAgent->is_active,
            'message' => "Agente IA {$status} exitosamente.",
        ]);
    }
}

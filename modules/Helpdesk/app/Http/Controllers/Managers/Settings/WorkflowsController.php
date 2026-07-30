<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\StoreWorkflowRequest;
use Modules\Helpdesk\Http\Requests\UpdateWorkflowRequest;
use Modules\Helpdesk\Models\Workflow;

class WorkflowsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.workflows.view')->only(['index']);
        $this->middleware('can:helpdesk.workflows.manage')->only(['create', 'store', 'edit', 'update', 'destroy', 'toggle']);
    }

    public function index(Request $request): View
    {
        $query = Workflow::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('trigger_type')) {
            $query->where('trigger_type', $request->trigger_type);
        }

        $workflows = $query->latest()->paginate(20);

        $statsRow = Workflow::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count,
            COALESCE(SUM(total_runs), 0) as total_runs_sum
        ')->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'active' => (int) $statsRow->active_count,
            'total_runs' => (int) $statsRow->total_runs_sum,
        ];

        return view('helpdesk::settings.workflows.index', compact('workflows', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.workflows.create');
    }

    public function store(StoreWorkflowRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['trigger_config'] = $data['trigger_config'] ? json_decode($data['trigger_config'], true) : null;
        $data['nodes'] = $data['nodes'] ? json_decode($data['nodes'], true) : null;

        Workflow::create($data);

        return redirect()
            ->route('settings.helpdesk.workflows.index')
            ->with('success', 'Workflow creado exitosamente.');
    }

    public function edit(Workflow $workflow): View
    {
        return view('helpdesk::settings.workflows.edit', compact('workflow'));
    }

    public function update(UpdateWorkflowRequest $request, Workflow $workflow): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['trigger_config'] = $data['trigger_config'] ? json_decode($data['trigger_config'], true) : null;
        $data['nodes'] = $data['nodes'] ? json_decode($data['nodes'], true) : null;

        $workflow->update($data);

        return redirect()
            ->route('settings.helpdesk.workflows.index')
            ->with('success', 'Workflow actualizado exitosamente.');
    }

    public function destroy(Workflow $workflow): RedirectResponse
    {
        $workflow->delete();

        return redirect()
            ->route('settings.helpdesk.workflows.index')
            ->with('success', 'Workflow eliminado exitosamente.');
    }

    public function toggle(Workflow $workflow): RedirectResponse
    {
        $workflow->update(['is_active' => ! $workflow->is_active]);

        $message = $workflow->is_active ? 'Workflow activado.' : 'Workflow desactivado.';

        return redirect()
            ->route('settings.helpdesk.workflows.index')
            ->with('success', $message);
    }
}

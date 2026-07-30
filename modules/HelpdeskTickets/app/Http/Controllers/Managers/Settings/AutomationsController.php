<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreAutomationRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateAutomationRequest;
use Modules\HelpdeskTickets\Models\Automation;

class AutomationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

    public function index(Request $request): View
    {
        $query = Automation::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $automations = $query->orderBy('order')->paginate(20);

        $stats = [
            'total' => Automation::count(),
            'active' => Automation::where('is_active', true)->count(),
            'inactive' => Automation::where('is_active', false)->count(),
            'total_runs' => (int) Automation::sum('run_count'),
        ];

        return view('helpdesktickets::managers.settings.automations.index', compact('automations', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesktickets::managers.settings.automations.create', [
            'triggerEvents' => Automation::$triggerEvents,
        ]);
    }

    public function store(StoreAutomationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['conditions'] = json_decode($validated['conditions'], true);
        $validated['actions'] = json_decode($validated['actions'], true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['user_id'] = auth()->id();

        Automation::create($validated);

        return redirect()
            ->route('manager.helpdesk.settings.automations.index')
            ->with('success', 'Automatizacion creada exitosamente.');
    }

    public function edit(Automation $automation): View
    {
        return view('helpdesktickets::managers.settings.automations.edit', [
            'automation' => $automation,
            'triggerEvents' => Automation::$triggerEvents,
        ]);
    }

    public function update(UpdateAutomationRequest $request, Automation $automation): RedirectResponse
    {
        $validated = $request->validated();

        $validated['conditions'] = json_decode($validated['conditions'], true);
        $validated['actions'] = json_decode($validated['actions'], true);
        $validated['is_active'] = $request->boolean('is_active', true);

        $automation->update($validated);

        return redirect()
            ->route('manager.helpdesk.settings.automations.index')
            ->with('success', 'Automatizacion actualizada exitosamente.');
    }

    public function destroy(Automation $automation): RedirectResponse
    {
        $automation->delete();

        return redirect()
            ->route('manager.helpdesk.settings.automations.index')
            ->with('success', 'Automatizacion eliminada exitosamente.');
    }
}

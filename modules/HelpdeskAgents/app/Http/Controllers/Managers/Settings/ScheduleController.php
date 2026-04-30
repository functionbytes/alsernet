<?php

namespace Modules\HelpdeskAgents\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\HelpdeskAgents\Http\Requests\StoreOncallRequest;
use Modules\HelpdeskAgents\Http\Requests\StoreShiftRequest;
use Modules\HelpdeskAgents\Http\Requests\StoreVacationRequest;
use Modules\HelpdeskAgents\Models\AgentShift;
use Modules\HelpdeskAgents\Models\AgentVacation;
use Modules\HelpdeskAgents\Models\OncallRotation;

class ScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.schedule.view')->only(['index', 'show']);
        $this->middleware('can:helpdesk.schedule.update')->only(['storeShift', 'destroyShift', 'storeVacation', 'destroyVacation', 'storeOncall', 'destroyOncall']);
    }

    public function index(): View
    {
        $shifts = AgentShift::query()->with('user')->orderBy('day_of_week')->orderBy('start_time')->get();
        $vacations = AgentVacation::query()->with('user')->latest()->get();
        $oncalls = OncallRotation::query()->with('currentUser')->latest()->get();
        $agents = User::query()->orderBy('firstname')->orderBy('lastname')->get();

        return view('helpdesk::settings.schedule.index', compact(
            'shifts', 'vacations', 'oncalls', 'agents'
        ));
    }

    // --- Shifts ---

    public function storeShift(StoreShiftRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['timezone'] = $validated['timezone'] ?? 'UTC';
        $validated['is_active'] = true;

        AgentShift::create($validated);

        return redirect()->route('settings.helpdesk.schedule.index')
            ->with('success', 'Turno creado correctamente.');
    }

    public function destroyShift(AgentShift $shift): RedirectResponse
    {
        $shift->delete();

        return redirect()->route('settings.helpdesk.schedule.index')
            ->with('success', 'Turno eliminado correctamente.');
    }

    // --- Vacations ---

    public function storeVacation(StoreVacationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        AgentVacation::create($validated);

        return redirect()->route('settings.helpdesk.schedule.index')
            ->with('success', 'Ausencia registrada correctamente.');
    }

    public function destroyVacation(AgentVacation $vacation): RedirectResponse
    {
        $vacation->delete();

        return redirect()->route('settings.helpdesk.schedule.index')
            ->with('success', 'Ausencia eliminada correctamente.');
    }

    // --- On-call rotations ---

    public function storeOncall(StoreOncallRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_active'] = true;

        OncallRotation::create($validated);

        return redirect()->route('settings.helpdesk.schedule.index')
            ->with('success', 'Rotacion de guardia creada correctamente.');
    }

    public function destroyOncall(OncallRotation $oncall): RedirectResponse
    {
        $oncall->delete();

        return redirect()->route('settings.helpdesk.schedule.index')
            ->with('success', 'Rotacion de guardia eliminada correctamente.');
    }
}

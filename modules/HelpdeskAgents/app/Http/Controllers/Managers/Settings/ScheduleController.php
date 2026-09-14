<?php

namespace Modules\HelpdeskAgents\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\HelpdeskAgents\Http\Requests\StoreOncallRequest;
use Modules\HelpdeskAgents\Http\Requests\StoreShiftRequest;
use Modules\HelpdeskAgents\Http\Requests\StoreVacationRequest;
use Modules\HelpdeskAgents\Models\AgentShift;
use Modules\HelpdeskAgents\Models\AgentVacation;
use Modules\HelpdeskAgents\Models\OncallRotation;
use Modules\HelpdeskTickets\Services\CatalogCacheService;

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

        // Antes: User::query()->get() sin filtro de rol — en un sistema con
        // fixtures de otros módulos mostraba prácticamente a todo el mundo
        // como "agente" seleccionable. Mismo catálogo cacheado (rol
        // helpdesk-agent + available) que ya usa el selector de Tickets.
        $agents = CatalogCacheService::agents();

        return view('helpdeskagents::settings.schedule.index', compact(
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
        $validated['current_user_id'] ??= $validated['user_ids'][0];

        // El handoff se calculaba nunca: la fila se guardaba con
        // next_handoff_at siempre null, así que AdvanceOncallRotations no
        // tenía forma de saber cuándo tocaba rotar. Se fija en el alta y se
        // recalcula en cada rotación (ver AdvanceOncallRotations).
        $validated['next_handoff_at'] = Carbon::parse($validated['started_at'])
            ->addHours($validated['shift_duration_hours']);

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

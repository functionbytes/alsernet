<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\AgentShift;
use Modules\Helpdesk\Models\AgentVacation;
use Modules\Helpdesk\Models\OncallRotation;

class ScheduleController extends Controller
{
    public function index(): View
    {
        $shifts = AgentShift::query()->with('user')->orderBy('day_of_week')->orderBy('start_time')->get();
        $vacations = AgentVacation::query()->with('user')->latest()->get();
        $oncalls = OncallRotation::query()->with('currentUser')->latest()->get();
        $agents = User::query()->orderBy('firstname')->orderBy('lastname')->get();

        return view('helpdesk::managers.settings.schedule.index', compact(
            'shifts', 'vacations', 'oncalls', 'agents'
        ));
    }

    // --- Shifts ---

    public function storeShift(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ], [
            'user_id.required' => 'El agente es obligatorio.',
            'user_id.exists' => 'El agente seleccionado no existe.',
            'day_of_week.required' => 'El dia es obligatorio.',
            'day_of_week.between' => 'El dia debe estar entre 0 (domingo) y 6 (sabado).',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'end_time.required' => 'La hora de fin es obligatoria.',
            'end_time.date_format' => 'La hora de fin debe tener formato HH:MM.',
            'end_time.after' => 'La hora de fin debe ser posterior a la de inicio.',
        ]);

        $validated['timezone'] = $validated['timezone'] ?? 'UTC';
        $validated['is_active'] = true;

        AgentShift::create($validated);

        return redirect()->route('manager.helpdesk.settings.schedule.index')
            ->with('success', 'Turno creado correctamente.');
    }

    public function destroyShift(AgentShift $shift): RedirectResponse
    {
        $shift->delete();

        return redirect()->route('manager.helpdesk.settings.schedule.index')
            ->with('success', 'Turno eliminado correctamente.');
    }

    // --- Vacations ---

    public function storeVacation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'reason' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:pending,approved,rejected'],
        ], [
            'user_id.required' => 'El agente es obligatorio.',
            'user_id.exists' => 'El agente seleccionado no existe.',
            'starts_at.required' => 'La fecha de inicio es obligatoria.',
            'ends_at.required' => 'La fecha de fin es obligatoria.',
            'ends_at.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
            'status.in' => 'El estado debe ser pendiente, aprobado o rechazado.',
        ]);

        AgentVacation::create($validated);

        return redirect()->route('manager.helpdesk.settings.schedule.index')
            ->with('success', 'Ausencia registrada correctamente.');
    }

    public function destroyVacation(AgentVacation $vacation): RedirectResponse
    {
        $vacation->delete();

        return redirect()->route('manager.helpdesk.settings.schedule.index')
            ->with('success', 'Ausencia eliminada correctamente.');
    }

    // --- On-call rotations ---

    public function storeOncall(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'shift_duration_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'started_at' => ['required', 'date'],
            'current_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'user_ids.required' => 'Debe seleccionar al menos un agente.',
            'user_ids.min' => 'Debe seleccionar al menos un agente.',
            'shift_duration_hours.required' => 'La duracion del turno es obligatoria.',
            'started_at.required' => 'La fecha de inicio es obligatoria.',
        ]);

        $validated['is_active'] = true;

        OncallRotation::create($validated);

        return redirect()->route('manager.helpdesk.settings.schedule.index')
            ->with('success', 'Rotacion de guardia creada correctamente.');
    }

    public function destroyOncall(OncallRotation $oncall): RedirectResponse
    {
        $oncall->delete();

        return redirect()->route('manager.helpdesk.settings.schedule.index')
            ->with('success', 'Rotacion de guardia eliminada correctamente.');
    }
}

<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\BusinessHour;

class BusinessHoursController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only('index');
        $this->middleware('can:helpdesk.settings.update')->only(['update', 'reset']);
    }

    public function index(): View
    {
        BusinessHour::initializeDefaults();

        $hours = BusinessHour::query()
            ->orderBy('day_of_week')
            ->get();

        $timezones = [
            'America/Mexico_City' => 'América/Ciudad de México (CST)',
            'America/Monterrey' => 'América/Monterrey (CST)',
            'America/Cancun' => 'América/Cancún (EST)',
            'America/Tijuana' => 'América/Tijuana (PST)',
            'America/Bogota' => 'América/Bogotá (COT)',
            'America/Lima' => 'América/Lima (PET)',
            'America/Santiago' => 'América/Santiago (CLT)',
            'America/Buenos_Aires' => 'América/Buenos Aires (ART)',
            'America/Sao_Paulo' => 'América/São Paulo (BRT)',
            'America/Caracas' => 'América/Caracas (VET)',
            'America/New_York' => 'América/Nueva York (EST)',
            'America/Chicago' => 'América/Chicago (CST)',
            'America/Los_Angeles' => 'América/Los Ángeles (PST)',
            'Europe/Madrid' => 'Europa/Madrid (CET)',
            'Europe/London' => 'Europa/Londres (GMT)',
            'Europe/Paris' => 'Europa/París (CET)',
            'Europe/Berlin' => 'Europa/Berlín (CET)',
            'UTC' => 'UTC',
        ];

        return view('helpdesk::settings.business-hours.index', compact('hours', 'timezones'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.is_open' => ['boolean'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i', 'after:hours.*.opens_at'],
            'timezone' => ['required', 'string', 'max:50', 'timezone'],
        ], [
            'hours.required' => 'Los horarios son obligatorios.',
            'hours.size' => 'Deben configurarse los 7 días de la semana.',
            'hours.*.opens_at.date_format' => 'La hora de apertura debe tener el formato HH:MM.',
            'hours.*.closes_at.date_format' => 'La hora de cierre debe tener el formato HH:MM.',
            'hours.*.closes_at.after' => 'La hora de cierre debe ser posterior a la de apertura.',
            'timezone.required' => 'La zona horaria es obligatoria.',
            'timezone.timezone' => 'La zona horaria seleccionada no es válida.',
        ]);

        $timezone = $request->input('timezone');

        foreach ($request->input('hours') as $dayOfWeek => $data) {
            $isOpen = isset($data['is_open']) && (bool) $data['is_open'];

            BusinessHour::query()
                ->where('day_of_week', $dayOfWeek)
                ->update([
                    'is_open' => $isOpen,
                    'opens_at' => $isOpen ? ($data['opens_at'] ?? null) : null,
                    'closes_at' => $isOpen ? ($data['closes_at'] ?? null) : null,
                    'timezone' => $timezone,
                ]);
        }

        return redirect()
            ->route('settings.helpdesk.business-hours')
            ->with('success', 'Horarios de atención actualizados correctamente.');
    }

    public function reset(): RedirectResponse
    {
        BusinessHour::query()->delete();
        BusinessHour::initializeDefaults();

        return redirect()
            ->route('settings.helpdesk.business-hours')
            ->with('success', 'Horarios restablecidos a los valores predeterminados.');
    }
}

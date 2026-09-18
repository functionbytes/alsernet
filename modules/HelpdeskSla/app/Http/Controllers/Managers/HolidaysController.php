<?php

namespace Modules\HelpdeskSla\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\HelpdeskSla\Http\Requests\Managers\StoreHolidayRequest;
use Modules\HelpdeskSla\Models\Holiday;

/**
 * Gestión de festivos del calendario de negocio. El motor de horas hábiles
 * (BusinessHoursCalculator) los trata como días no laborables al calcular los
 * vencimientos SLA y el escalado.
 */
class HolidaysController extends Controller
{
    public function index(): View
    {
        $holidays = Holiday::query()
            ->orderByRaw('is_recurring DESC')
            ->orderBy('date')
            ->get();

        return view('helpdesksla::holidays.index', compact('holidays'));
    }

    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        $data = $request->safe()->all();
        $data['is_recurring'] = $request->has('is_recurring') ? '1' : '0';

        Holiday::create($data);

        return redirect()
            ->route('helpdesksla.holidays.index')
            ->with('success', 'Festivo añadido.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return redirect()
            ->route('helpdesksla.holidays.index')
            ->with('success', 'Festivo eliminado.');
    }
}

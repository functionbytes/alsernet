<?php

namespace Modules\HelpdeskSla\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskSla\Http\Requests\Managers\StoreHolidayRequest;
use Modules\HelpdeskSla\Models\Holiday;
use Modules\HelpdeskSla\Services\ACorunaHolidayCalendar;

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

        return view('helpdesksla::holidays.index', [
            'holidays' => $holidays,
            'importYears' => ACorunaHolidayCalendar::availableYears(),
            'coverageWarning' => $this->coverageWarning($holidays),
        ]);
    }

    /**
     * Avisa cuando el calendario curado se está agotando, para que alguien lo
     * revise antes de que un año quede sin festivos por olvido:
     * - Si ya estamos en el último año verificado en ACorunaHolidayCalendar (o
     *   más allá), falta investigar y añadir el año siguiente al código.
     * - Si el año siguiente SÍ está verificado en código pero nadie le ha dado
     *   a "Sincronizar" todavía, lo recuerda (comprueba por mes-día para no
     *   contar como "sin sincronizar" un año cuyos festivos ya caen todos en
     *   fechas recurrentes).
     */
    /**
     * @param  Collection<int, Holiday>  $holidays
     */
    private function coverageWarning(Collection $holidays): ?string
    {
        $years = ACorunaHolidayCalendar::availableYears();
        $maxYear = max($years);
        $currentYear = now()->year;
        $nextYear = $currentYear + 1;

        if ($currentYear >= $maxYear) {
            return "El calendario oficial verificado de A Coruña llega hasta {$maxYear}. Cuando el Concello y la Xunta publiquen el de ".($maxYear + 1).' (normalmente entre verano y otoño), hay que añadirlo a ACorunaHolidayCalendar y sincronizarlo aquí.';
        }

        if (! in_array($nextYear, $years, true)) {
            return null;
        }

        $recurringMonthDays = $holidays->where('is_recurring', true)
            ->map(fn (Holiday $h) => $h->date->format('m-d'))
            ->all();

        $nextYearSynced = collect(ACorunaHolidayCalendar::forYear($nextYear))
            ->every(function (array $entry) use ($holidays, $recurringMonthDays) {
                if (in_array(substr($entry['date'], 5), $recurringMonthDays, true)) {
                    return true;
                }

                return $holidays->contains(fn (Holiday $h) => $h->date->format('Y-m-d') === $entry['date']);
            });

        if (! $nextYearSynced) {
            return "Ya hay calendario verificado para {$nextYear} — sincronízalo con el panel de la izquierda cuando quieras.";
        }

        return null;
    }

    /**
     * Carga el calendario oficial de A Coruña (Galicia) para un año concreto —
     * ver ACorunaHolidayCalendar por qué esto no es una API en vivo. Idempotente
     * (updateOrCreate por fecha) y no duplica un día ya cubierto por un festivo
     * recurrente existente (mismo mes-día, comparación igual que
     * BusinessHoursCalculator::isHoliday()).
     */
    public function import(Request $request): RedirectResponse
    {
        $years = ACorunaHolidayCalendar::availableYears();

        $request->validate([
            'year' => ['required', 'integer', 'in:'.implode(',', $years)],
        ], [
            'year.in' => 'Solo hay calendario verificado para: '.implode(', ', $years).'.',
        ]);

        $year = (int) $request->input('year');

        $recurringMonthDays = Holiday::query()
            ->where('is_recurring', true)
            ->get()
            ->map(fn (Holiday $h) => $h->date->format('m-d'))
            ->all();

        $created = 0;
        $updated = 0;

        foreach (ACorunaHolidayCalendar::forYear($year) as $entry) {
            $monthDay = substr($entry['date'], 5);

            if (in_array($monthDay, $recurringMonthDays, true)) {
                continue;
            }

            $holiday = Holiday::query()->where('date', $entry['date'])->first();

            Holiday::updateOrCreate(
                ['date' => $entry['date']],
                ['name' => $entry['name'], 'is_recurring' => false]
            );

            $holiday ? $updated++ : $created++;
        }

        return redirect()
            ->route('helpdesksla.holidays.index')
            ->with('success', "Calendario {$year} de A Coruña sincronizado: {$created} festivos añadidos, {$updated} actualizados.");
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

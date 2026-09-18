<?php

namespace Modules\HelpdeskChatFlow\Services\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;

/**
 * Evaluates whether "now" falls within a configured business-hours window
 * (active weekdays + time range in a given timezone). Shared by the live
 * executor and the test simulator so both behave identically.
 */
trait EvaluatesBusinessHours
{
    /**
     * @param  array<string,mixed>  $data  Node data: timezone, days (ISO 1-7), start_time, end_time
     */
    protected function isWithinBusinessHours(array $data): bool
    {
        $timezone = $data['timezone'] ?? config('app.timezone', 'UTC');
        $days = array_map('intval', (array) ($data['days'] ?? [1, 2, 3, 4, 5])); // Mon-Fri (ISO: 1=Mon..7=Sun)
        $start = $this->minutesOfDay($data['start_time'] ?? '09:00');
        $end = $this->minutesOfDay($data['end_time'] ?? '18:00');

        try {
            $now = now()->setTimezone($timezone);
        } catch (\Throwable) {
            // Timezone inválida escrita a mano en el editor visual (input de
            // texto libre, sin validar): antes caía en la del servidor sin
            // dejar rastro. Se mantiene el fallback, pero ahora queda avisado.
            Log::warning('EvaluatesBusinessHours: timezone inválida en el nodo de horario de atención, se usa la del servidor.', [
                'timezone' => $timezone,
            ]);
            $now = now();
        }

        $current = (int) $now->hour * 60 + (int) $now->minute;

        // Overnight window (e.g. 22:00–06:00) spans midnight: "within" is either
        // after the start (yesterday's shift) or before the end (today, pre-dawn).
        if ($end <= $start) {
            if ($current >= $start) {
                return $this->isActiveBusinessDay($now, $days);
            }

            if ($current < $end) {
                // Pre-dawn stretch belongs to the shift that began the previous day:
                // tanto el día de la semana como el festivo se comprueban sobre ayer.
                return $this->isActiveBusinessDay($now->copy()->subDay(), $days);
            }

            return false;
        }

        if ($current < $start || $current > $end) {
            return false;
        }

        return $this->isActiveBusinessDay($now, $days);
    }

    /**
     * ¿Ese día cae en un día activo del nodo y no es festivo? El calendario de
     * festivos es el de HelpdeskSla (dependencia blanda: sin ese módulo
     * instalado nunca hay festivos, igual que el comportamiento previo).
     *
     * @param  array<int>  $days
     */
    private function isActiveBusinessDay(Carbon $day, array $days): bool
    {
        if (! in_array((int) $day->isoWeekday(), $days, true)) {
            return false;
        }

        return ! $this->isHoliday($day);
    }

    private function isHoliday(Carbon $day): bool
    {
        if (! class_exists(BusinessHoursCalculator::class)) {
            return false;
        }

        $calculator = app(BusinessHoursCalculator::class);

        return $calculator->isHoliday($day, $calculator->holidays());
    }

    /**
     * Convert an "H:i" (or "H") time string to minutes since midnight, tolerating
     * unpadded hours like "9:00" that string comparison would mis-order.
     */
    private function minutesOfDay(string $time): int
    {
        [$h, $m] = array_pad(explode(':', trim($time)), 2, '0');

        return ((int) $h) * 60 + ((int) $m);
    }
}

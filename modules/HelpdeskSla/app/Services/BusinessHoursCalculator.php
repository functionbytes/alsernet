<?php

namespace Modules\HelpdeskSla\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\BusinessHour;
use Modules\HelpdeskSla\Models\Holiday;

/**
 * Único algoritmo de horas hábiles del producto sobre el calendario real
 * helpdesk_business_hours (con fallback a config helpdesksla.default_business_hours).
 *
 * Extraído de ConversationSlaService::addBusinessHours() para poder reutilizarlo
 * desde otros módulos sin duplicarlo:
 *
 * - ConversationSlaService (este módulo): vencimientos SLA de conversaciones.
 * - Modules\HelpdeskTickets\Services\EscalationService: evalúa los umbrales de
 *   antigüedad en horas hábiles cuando helpdesktickets.escalation.business_hours
 *   está activo (dependencia blanda vía class_exists: sin este módulo, el
 *   escalado sigue en horas naturales).
 */
class BusinessHoursCalculator
{
    /**
     * Misma clave que usaba ConversationSlaService para no invalidar los caches
     * calientes al extraer la clase.
     */
    public const CACHE_KEY = 'helpdesksla:business_hours_schedule';

    public const HOLIDAYS_CACHE_KEY = 'helpdesksla:business_hours_holidays';

    /**
     * Add a number of hours to a start date, optionally honouring the configured
     * business-hours calendar (helpdesk_business_hours, with a config fallback).
     */
    public function addBusinessHours(Carbon|string $start, int $hours, bool $businessHoursOnly = true): Carbon
    {
        $start = $start instanceof Carbon ? $start->copy() : Carbon::parse($start);

        if (! $businessHoursOnly || $hours <= 0) {
            return $start->copy()->addHours($hours);
        }

        $timezone = (string) config('helpdesksla.default_business_hours.timezone', 'Europe/Madrid');
        $schedule = $this->schedule();
        $cursor = $start->copy()->setTimezone($timezone);
        $remaining = $hours * 60;
        $guard = 0;

        $holidays = $this->holidays();

        while ($remaining > 0 && $guard++ < 1000) {
            $day = $schedule[$cursor->dayOfWeek] ?? null;

            // Día no laborable (fuera del calendario) o festivo: se salta entero.
            if ($day === null || $this->isHoliday($cursor, $holidays)) {
                $cursor = $cursor->addDay()->startOfDay();

                continue;
            }

            [$openHour, $openMinute] = array_map('intval', explode(':', $day['open']));
            [$closeHour, $closeMinute] = array_map('intval', explode(':', $day['close']));

            $open = $cursor->copy()->setTime($openHour, $openMinute);
            $close = $cursor->copy()->setTime($closeHour, $closeMinute);

            if ($cursor->lessThan($open)) {
                $cursor = $open->copy();
            }

            if ($cursor->greaterThanOrEqualTo($close)) {
                $cursor = $cursor->addDay()->startOfDay();

                continue;
            }

            $available = (int) round(abs($cursor->diffInMinutes($close)));

            if ($remaining <= $available) {
                $cursor = $cursor->addMinutes($remaining);
                $remaining = 0;
            } else {
                $remaining -= $available;
                $cursor = $cursor->addDay()->startOfDay();
            }
        }

        return $cursor->setTimezone($start->getTimezone());
    }

    /**
     * Business-hours calendar keyed by Carbon dayOfWeek (0=Sunday..6=Saturday).
     *
     * @return array<int, array{open: string, close: string}>
     */
    public function schedule(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function (): array {
            $rows = BusinessHour::query()->where('is_open', true)->get();

            if ($rows->isEmpty()) {
                return config('helpdesksla.default_business_hours.days', []);
            }

            $map = [];

            foreach ($rows as $row) {
                if (! $row->opens_at || ! $row->closes_at) {
                    continue;
                }

                $map[(int) $row->day_of_week] = [
                    'open' => substr((string) $row->opens_at, 0, 5),
                    'close' => substr((string) $row->closes_at, 0, 5),
                ];
            }

            return $map;
        });
    }

    /**
     * Festivos del calendario de negocio. Los fijos (is_recurring) se indexan por
     * 'm-d' para repetirse cada año; los puntuales por 'Y-m-d'. Degrada a lista
     * vacía si el módulo de festivos aún no está migrado.
     *
     * @return array{recurring: array<string, true>, dates: array<string, true>}
     */
    public function holidays(): array
    {
        return Cache::remember(self::HOLIDAYS_CACHE_KEY, 300, function (): array {
            $recurring = [];
            $dates = [];

            try {
                foreach (Holiday::all() as $holiday) {
                    if (! $holiday->date) {
                        continue;
                    }

                    if ($holiday->is_recurring) {
                        $recurring[$holiday->date->format('m-d')] = true;
                    } else {
                        $dates[$holiday->date->format('Y-m-d')] = true;
                    }
                }
            } catch (\Throwable) {
                // Tabla ausente: sin festivos (comportamiento previo).
            }

            return ['recurring' => $recurring, 'dates' => $dates];
        });
    }

    /**
     * ¿La fecha cae en festivo? Público para reutilizarlo desde el cálculo SLA
     * de tickets (Ticket::calculateBusinessTime), que tiene su propio bucle de
     * horas hábiles pero comparte la misma fuente de festivos.
     *
     * @param  array{recurring: array<string, true>, dates: array<string, true>}  $holidays
     */
    public function isHoliday(Carbon $cursor, array $holidays): bool
    {
        return isset($holidays['dates'][$cursor->format('Y-m-d')])
            || isset($holidays['recurring'][$cursor->format('m-d')]);
    }
}

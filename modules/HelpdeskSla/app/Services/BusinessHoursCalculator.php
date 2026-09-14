<?php

namespace Modules\HelpdeskSla\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

        $schedule = $this->schedule();

        if (! $this->scheduleHasOpenDays($schedule)) {
            Log::warning('HelpdeskSla: calendario de horas hábiles vacío, se degrada a horas naturales.', ['hours' => $hours]);

            return $start->copy()->addHours($hours);
        }

        $timezone = $this->resolveTimezone($schedule);
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

        // El guard se agotó (calendario inconsistente/loop defensivo): el resto
        // pendiente se degrada a horas naturales en vez de devolver un cursor
        // desbocado (~2.7 años en el futuro tras 1000 iteraciones de 1 día).
        if ($remaining > 0) {
            Log::warning('HelpdeskSla: el cálculo de horas hábiles agotó el límite de iteraciones, se degrada el resto a horas naturales.', [
                'hours' => $hours,
                'remaining_minutes' => $remaining,
            ]);

            $cursor = $cursor->addMinutes($remaining);
        }

        return $cursor->setTimezone($start->getTimezone());
    }

    /**
     * ¿Hay al menos un día laborable en el calendario? Ignora la clave
     * 'timezone' (metadato, no un día de la semana).
     *
     * @param  array<int|string, mixed>  $schedule
     */
    private function scheduleHasOpenDays(array $schedule): bool
    {
        foreach (array_keys($schedule) as $day) {
            if (is_int($day)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Zona horaria del calendario de negocio. Sale del propio calendario
     * cacheado (columna helpdesk_business_hours.timezone) para no asumir la
     * zona por defecto de config() cuando las filas reales usan otra distinta
     * (p.ej. initializeDefaults() siembra America/Mexico_City).
     *
     * @param  array<int|string, mixed>  $schedule
     */
    private function resolveTimezone(array $schedule): string
    {
        return (string) ($schedule['timezone'] ?? config('helpdesksla.default_business_hours.timezone', 'Europe/Madrid'));
    }

    /**
     * Minutos hábiles reales transcurridos entre dos fechas (no la diferencia
     * en minutos naturales). Usado por ConversationSlaService::percentUsed()
     * para que el aviso de "cerca del SLA" no se consuma durante fines de
     * semana/festivos cuando la política es de horas hábiles.
     */
    public function businessMinutesBetween(Carbon $start, Carbon $end): int
    {
        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        $schedule = $this->schedule();

        if (! $this->scheduleHasOpenDays($schedule)) {
            return (int) abs($start->diffInMinutes($end));
        }

        $timezone = $this->resolveTimezone($schedule);
        $cursor = $start->copy()->setTimezone($timezone);
        $end = $end->copy()->setTimezone($timezone);
        $holidays = $this->holidays();
        $minutes = 0;
        $guard = 0;

        while ($cursor->lessThan($end) && $guard++ < 1000) {
            $day = $schedule[$cursor->dayOfWeek] ?? null;

            if ($day === null || $this->isHoliday($cursor, $holidays)) {
                $cursor = $cursor->addDay()->startOfDay();

                continue;
            }

            [$openHour, $openMinute] = array_map('intval', explode(':', $day['open']));
            [$closeHour, $closeMinute] = array_map('intval', explode(':', $day['close']));

            $open = $cursor->copy()->setTime($openHour, $openMinute);
            $close = $cursor->copy()->setTime($closeHour, $closeMinute);

            $segmentStart = $cursor->greaterThan($open) ? $cursor : $open;
            $segmentEnd = $end->lessThan($close) ? $end : $close;

            if ($segmentStart->lessThan($segmentEnd)) {
                $minutes += (int) round(abs($segmentStart->diffInMinutes($segmentEnd)));
            }

            if ($end->lessThanOrEqualTo($close)) {
                break;
            }

            $cursor = $cursor->addDay()->startOfDay();
        }

        return $minutes;
    }

    /**
     * Business-hours calendar keyed by Carbon dayOfWeek (0=Sunday..6=Saturday),
     * plus a 'timezone' string entry taken from the rows themselves (no
     * collision with the int day keys). Falls back to the config default
     * calendar/timezone when no rows exist, which is why resolveTimezone()
     * still checks config() when the 'timezone' key is absent.
     *
     * @return array<int|string, mixed>
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
                if ($row->timezone) {
                    $map['timezone'] ??= $row->timezone;
                }

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

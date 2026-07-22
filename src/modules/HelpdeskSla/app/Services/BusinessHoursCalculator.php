<?php

namespace Modules\HelpdeskSla\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\BusinessHour;

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

        while ($remaining > 0 && $guard++ < 1000) {
            $day = $schedule[$cursor->dayOfWeek] ?? null;

            if ($day === null) {
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
}

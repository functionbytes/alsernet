<?php

namespace Modules\Helpdesk\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\BusinessHour;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;

class BusinessHoursService
{
    private const CACHE_TTL = 60; // seconds

    /**
     * Determine if the business is open right now based on stored hours.
     * Returns true when open, false when outside hours, on a holiday, or when
     * no records found. Los festivos se consultan contra el calendario de
     * HelpdeskSla si ese módulo está instalado (dependencia blanda); sin él,
     * ningún día se trata como festivo. Result is cached for 1 minute to
     * avoid repeated DB queries on every webhook.
     *
     * When the "Horarios de atención" panel toggle (Settings → Business →
     * Features) is OFF, the business is considered always open — no
     * restrictive schedule applies, so off-hours auto-replies never fire.
     */
    public function isOpenNow(): bool
    {
        if (! helpdesk_business_hours_feature_enabled()) {
            return true;
        }

        return Cache::remember('helpdesk:business_hours_open', self::CACHE_TTL, function () {
            $hour = BusinessHour::query()
                ->where('day_of_week', now()->dayOfWeek)
                ->first();

            if (! $hour || ! $hour->is_open) {
                return false;
            }

            $timezone = $hour->timezone ?? config('app.timezone', 'UTC');

            try {
                $now = Carbon::now($timezone);
            } catch (\Throwable) {
                $now = Carbon::now(config('app.timezone', 'UTC'));
            }

            if ($this->isHoliday($now)) {
                return false;
            }

            $opens = Carbon::parse($now->format('Y-m-d').' '.$hour->opens_at, $timezone);
            $closes = Carbon::parse($now->format('Y-m-d').' '.$hour->closes_at, $timezone);

            // Horario nocturno que cruza medianoche (p. ej. 22:00-02:00): closes_at
            // cae "antes" que opens_at al parsearlos sobre el mismo día calendario.
            if ($closes->lessThanOrEqualTo($opens)) {
                if ($now->greaterThanOrEqualTo($opens)) {
                    $closes->addDay();
                } else {
                    $opens->subDay();
                }
            }

            return $now->between($opens, $closes);
        });
    }

    /**
     * Invalidate the cached open/closed state.
     */
    public function forgetCache(): void
    {
        Cache::forget('helpdesk:business_hours_open');
    }

    /**
     * ¿"Hoy" (ya en la timezone del horario) es festivo según el calendario
     * de HelpdeskSla? Dependencia blanda: sin ese módulo instalado nunca hay
     * festivos, igual que el comportamiento previo a este cambio.
     */
    private function isHoliday(Carbon $now): bool
    {
        if (! class_exists(BusinessHoursCalculator::class)) {
            return false;
        }

        $calculator = app(BusinessHoursCalculator::class);

        return $calculator->isHoliday($now, $calculator->holidays());
    }
}

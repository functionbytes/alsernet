<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;

class BusinessFeatureSettingsService
{
    public const KEYS = ['business_hours_enabled', 'off_hours_enabled', 'greeting_enabled', 'farewell_enabled'];

    public function all(): array
    {
        return [
            'business_hours_enabled' => helpdesk_business_hours_feature_enabled(),
            'off_hours_enabled' => helpdesk_off_hours_feature_enabled(),
            'greeting_enabled' => helpdesk_greeting_feature_enabled(),
            'farewell_enabled' => helpdesk_farewell_feature_enabled(),
        ];
    }

    public function update(array $values): void
    {
        foreach (self::KEYS as $key) {
            Setting::set("business.{$key}", ($values[$key] ?? false) ? '1' : '0', 'business');
        }

        $this->forgetCaches();
    }

    /**
     * isOpenNow() (BusinessHoursService) y el calendario de horas hábiles de
     * HelpdeskSla cachean el estado de horarios: sin este forget, un cambio de
     * toggle tarda hasta 5 min en reflejarse en la respuesta automática fuera
     * de horario y en los vencimientos SLA. Mismo patrón que
     * BusinessHoursController::forgetBusinessHoursCaches().
     */
    private function forgetCaches(): void
    {
        cache()->forget('helpdesk:business_hours_open');

        if (class_exists(BusinessHoursCalculator::class)) {
            Cache::forget(BusinessHoursCalculator::CACHE_KEY);
        }
    }
}

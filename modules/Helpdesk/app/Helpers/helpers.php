<?php

use Modules\Helpdesk\Models\Setting;
use Nwidart\Modules\Facades\Module;

if (! function_exists('helpdesk_feature_enabled')) {
    /**
     * Check whether a Helpdesk UI feature is enabled.
     *
     * Feature keys map to `features.feature_{$feature}_enabled` in helpdesk_settings.
     * Defaults to true so every feature is visible when no DB record exists yet.
     */
    function helpdesk_feature_enabled(string $feature): bool
    {
        $settings = cache()->remember('helpdesk_features', now()->addMinutes(10), function () {
            try {
                return Setting::allAsArray('features');
            } catch (Throwable) {
                return [];
            }
        });

        $key = "features.feature_{$feature}_enabled";

        if (! array_key_exists($key, $settings)) {
            return true;
        }

        return filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
    }
}

if (! function_exists('helpdesk_tickets_enabled')) {
    /**
     * Check whether the HelpdeskTickets integration module is active.
     * Respects the `helpdesk.tickets.enabled` config key as a kill switch.
     */
    function helpdesk_tickets_enabled(): bool
    {
        if (! config('helpdesk.tickets.enabled', true)) {
            return false;
        }

        return Module::find('HelpdeskTickets')?->isEnabled() ?? false;
    }
}

if (! function_exists('helpdesk_prestashop_enabled')) {
    /**
     * Check whether the HelpdeskPrestashop integration module is active.
     */
    function helpdesk_prestashop_enabled(): bool
    {
        return Module::find('HelpdeskPrestashop')?->isEnabled() ?? false;
    }
}

if (! function_exists('helpdesk_erp_enabled')) {
    /**
     * Check whether the HelpdeskErp integration module is active.
     */
    function helpdesk_erp_enabled(): bool
    {
        return Module::find('HelpdeskErp')?->isEnabled() ?? false;
    }
}

if (! function_exists('helpdesk_document_enabled')) {
    /**
     * Check whether the HelpdeskDocument inbox-integration module is active.
     */
    function helpdesk_document_enabled(): bool
    {
        return Module::find('HelpdeskDocument')?->isEnabled() ?? false;
    }
}

if (! function_exists('safe_route')) {
    /**
     * Generate a route URL, returning '#' if the route does not exist.
     * Useful for optional module routes that may not be registered.
     */
    function safe_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        try {
            return route($name, $parameters, $absolute);
        } catch (Throwable) {
            return '#';
        }
    }
}

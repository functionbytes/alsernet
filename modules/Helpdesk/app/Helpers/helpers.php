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

if (! function_exists('helpdesk_setting_bool')) {
    /**
     * Read a boolean toggle from helpdesk_settings, tolerating an unavailable
     * or unmigrated database (empty DB, `php artisan migrate`, package:discover
     * in CI). Several module ServiceProviders call the `helpdesk_*_enabled()`
     * helpers during boot(); without this guard a missing `helpdesk_settings`
     * table aborts every artisan command, including the migration that would
     * create it. On DB error the provided default applies — same value the
     * toggle would have with no row present — so normal runtime behavior is
     * unchanged.
     */
    function helpdesk_setting_bool(string $key, string $default): bool
    {
        try {
            $value = Setting::get($key, $default);
        } catch (Throwable) {
            $value = $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

if (! function_exists('helpdesk_tickets_enabled')) {
    /**
     * Check whether the HelpdeskTickets integration is active.
     * Respects the `helpdesk.tickets.enabled` config key as a kill switch,
     * requires the module installed+enabled, and the admin toggle
     * (Settings → Integraciones, stored as `tickets.integration_enabled`).
     */
    function helpdesk_tickets_enabled(): bool
    {
        if (! config('helpdesk.tickets.enabled', true)) {
            return false;
        }

        if (! (Module::find('HelpdeskTickets')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('tickets.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_prestashop_enabled')) {
    /**
     * Check whether the HelpdeskPrestashop integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_prestashop_enabled(): bool
    {
        if (! (Module::find('HelpdeskPrestashop')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('prestashop.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_erp_enabled')) {
    /**
     * Check whether the HelpdeskErp integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_erp_enabled(): bool
    {
        if (! (Module::find('HelpdeskErp')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('erp.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_document_enabled')) {
    /**
     * Check whether the HelpdeskDocument inbox-integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_document_enabled(): bool
    {
        if (! (Module::find('HelpdeskDocument')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('document.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_livechat_enabled')) {
    /**
     * Check whether the HelpdeskLivechat integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_livechat_enabled(): bool
    {
        if (! (Module::find('HelpdeskLivechat')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('livechat.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_chatflow_enabled')) {
    /**
     * Check whether the HelpdeskChatFlow integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_chatflow_enabled(): bool
    {
        if (! (Module::find('HelpdeskChatFlow')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('chatflow.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_sla_enabled')) {
    /**
     * Check whether the HelpdeskSla integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_sla_enabled(): bool
    {
        if (! (Module::find('HelpdeskSla')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('sla.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_compliance_enabled')) {
    /**
     * Check whether the HelpdeskCompliance integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_compliance_enabled(): bool
    {
        if (! (Module::find('HelpdeskCompliance')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('compliance.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_social_enabled')) {
    /**
     * Check whether the HelpdeskSocial integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_social_enabled(): bool
    {
        if (! (Module::find('HelpdeskSocial')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('social.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_translate_enabled')) {
    /**
     * Check whether the HelpdeskTranslate integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_translate_enabled(): bool
    {
        if (! (Module::find('HelpdeskTranslate')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('translate.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_agents_enabled')) {
    /**
     * Check whether the HelpdeskAgents integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_agents_enabled(): bool
    {
        if (! (Module::find('HelpdeskAgents')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('agents.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_campaigns_enabled')) {
    /**
     * Check whether the HelpdeskCampaigns integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_campaigns_enabled(): bool
    {
        if (! (Module::find('HelpdeskCampaigns')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('campaigns.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_contacts_enabled')) {
    /**
     * Check whether the HelpdeskContacts integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_contacts_enabled(): bool
    {
        if (! (Module::find('HelpdeskContacts')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('contacts.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_analytics_enabled')) {
    /**
     * Check whether the HelpdeskAnalytics integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_analytics_enabled(): bool
    {
        if (! (Module::find('HelpdeskAnalytics')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('analytics.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_helpcenter_enabled')) {
    /**
     * Check whether the HelpdeskHelpcenter integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_helpcenter_enabled(): bool
    {
        if (! (Module::find('HelpdeskHelpcenter')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('helpcenter.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_emaillog_enabled')) {
    /**
     * Check whether the HelpdeskEmailLog integration is active: module
     * installed+enabled and the admin toggle in Settings → Integraciones.
     */
    function helpdesk_emaillog_enabled(): bool
    {
        if (! (Module::find('HelpdeskEmailLog')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('emaillog.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_integration_enabled')) {
    /**
     * Check whether the HelpdeskIntegration (external provider catalog) is
     * active: module installed+enabled and the admin toggle in Settings →
     * Integraciones.
     */
    function helpdesk_integration_enabled(): bool
    {
        if (! (Module::find('HelpdeskIntegration')?->isEnabled() ?? false)) {
            return false;
        }

        return helpdesk_setting_bool('integration.integration_enabled', '1');
    }
}

if (! function_exists('helpdesk_integration_identity_sms_enabled')) {
    /**
     * Check whether the SMS channel is enabled for the customer identity
     * verification gate (Settings → Integraciones). Defaults to OFF: the
     * channel is fully implemented but disabled until explicitly turned on.
     */
    function helpdesk_integration_identity_sms_enabled(): bool
    {
        return helpdesk_setting_bool('integration.identity_sms_enabled', '0');
    }
}

if (! function_exists('helpdesk_business_hours_feature_enabled')) {
    /**
     * Check whether the "Horarios de atención" panel toggle is active
     * (Settings → Business → Features). Defaults to ON.
     */
    function helpdesk_business_hours_feature_enabled(): bool
    {
        return helpdesk_setting_bool('business.business_hours_enabled', '1');
    }
}

if (! function_exists('helpdesk_off_hours_feature_enabled')) {
    /**
     * Check whether the "Fuera de horario" panel toggle is active
     * (Settings → Business → Features). Defaults to ON.
     */
    function helpdesk_off_hours_feature_enabled(): bool
    {
        return helpdesk_setting_bool('business.off_hours_enabled', '1');
    }
}

if (! function_exists('helpdesk_greeting_feature_enabled')) {
    /**
     * Check whether the "Bienvenida" panel toggle is active
     * (Settings → Business → Features). Defaults to ON.
     */
    function helpdesk_greeting_feature_enabled(): bool
    {
        return helpdesk_setting_bool('business.greeting_enabled', '1');
    }
}

if (! function_exists('helpdesk_farewell_feature_enabled')) {
    /**
     * Check whether the "Despedida" panel toggle is active
     * (Settings → Business → Features). Defaults to ON.
     */
    function helpdesk_farewell_feature_enabled(): bool
    {
        return helpdesk_setting_bool('business.farewell_enabled', '1');
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

if (! function_exists('safe_external_url')) {
    /**
     * Devuelve la URL solo si su esquema es http/https; en cualquier otro caso
     * (javascript:, data:, vbfile:, esquema ausente o valor no-string) devuelve
     * el fallback. Evita XSS por esquema al renderizar en href="{{ ... }}"
     * URLs de origen no confiable (clientes, ERP, permalinks de plataformas).
     */
    function safe_external_url(mixed $url, string $fallback = '#'): string
    {
        if (! is_string($url) || $url === '') {
            return $fallback;
        }

        $scheme = strtolower((string) parse_url(trim($url), PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : $fallback;
    }
}

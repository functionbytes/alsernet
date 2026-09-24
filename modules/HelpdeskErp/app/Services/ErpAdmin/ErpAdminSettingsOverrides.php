<?php

namespace Modules\HelpdeskErp\Services\ErpAdmin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\HelpdeskErp\Models\ErpAdminSetting;

/**
 * Aplica sobre config() los ajustes guardados en «Ajustes de Gestión»
 * (tabla helpdesk_erp_settings). ErpChatExtServiceProvider la llama en cada
 * boot(), así que es barata:
 *
 *  - la lectura se guarda en la caché de Laravel (una clave, solo con lo
 *    guardado, nunca con defaults) y se memoriza en el contenedor;
 *  - si la tabla aún no existe (migración pendiente) se recuerda «vacío»
 *    unos minutos;
 *  - guardar llama a refresh(), que invalida la caché sin repoblarla (así una
 *    escritura dentro de la transacción de un test no se queda en la caché).
 *
 * Antes de pisar nada se copian los valores de config/.env en
 * config('helpdeskErp.ext.admin.defaults'): son los de «Restablecer».
 */
final class ErpAdminSettingsOverrides
{
    public const TABLE = 'helpdesk_erp_settings';

    public const CONNECTION = 'helpdesk';

    public const CACHE_KEY = 'helpdeskerp:admin_settings:v1';

    private const MEMO = 'helpdeskerp.admin_settings.stored';

    private const DEFAULTS_KEY = 'helpdeskErp.ext.admin.defaults';

    private const NOT_READY_TTL = 300;

    /** Tipos de aviso del resumen → códigos de alerta de ErpChatOverview. */
    public const ALERT_CODES = [
        'risk' => ['risk_exceeded'],
        'debt' => ['pending_debt'],
        'inactive' => ['inactive'],
        'lopd' => ['no_commercial_consent'],
        'expiry' => ['voucher_expiring', 'bonus_expiring'],
        'served' => ['order_served'],
    ];

    public const TTL_STATES = ['ok', 'detail', 'blocked', 'unavailable', 'down'];

    /** Clave lógica (la de la tabla) → clave de config que pisa. */
    public const MAP = [
        'chat_ttl.ok' => 'helpdeskErp.chat_ttl.ok',
        'chat_ttl.detail' => 'helpdeskErp.chat_ttl.detail',
        'chat_ttl.blocked' => 'helpdeskErp.chat_ttl.blocked',
        'chat_ttl.unavailable' => 'helpdeskErp.chat_ttl.unavailable',
        'chat_ttl.down' => 'helpdeskErp.chat_ttl.down',
        'overview.orders_limit' => 'helpdeskErp.chat_overview_orders_limit',
        'alerts.expiry_days' => 'helpdeskErp.chat_expiry_warning_days',
        'alerts.enabled' => 'helpdeskErp.chat_alerts',
        'linking.auto' => 'helpdeskErp.auto_link',
        'tracking.urls' => 'helpdeskErp.tracking.templates',
        'metrics.enabled' => 'helpdeskErp.ext.admin.metrics.enabled',
        'metrics.retention_days' => 'helpdeskErp.ext.admin.metrics.retention_days',
    ];

    public static function apply(): void
    {
        self::captureDefaults();

        foreach (self::stored() as $key => $value) {
            $normalized = self::normalize($key, $value);
            if ($normalized !== null) {
                config([self::MAP[$key] => $normalized]);
            }
        }

        self::syncTrackingAliases();
    }

    public static function refresh(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable) {
            // Sin caché no hay nada que invalidar.
        }

        self::captureDefaults();

        foreach (self::defaults() as $key => $value) {
            config([self::MAP[$key] => $value]);
        }

        $stored = self::readTable() ?? [];
        app()->instance(self::MEMO, $stored);

        foreach ($stored as $key => $value) {
            $normalized = self::normalize($key, $value);
            if ($normalized !== null) {
                config([self::MAP[$key] => $normalized]);
            }
        }

        self::syncTrackingAliases();
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        self::captureDefaults();

        return (array) config(self::DEFAULTS_KEY, []);
    }

    /**
     * @return array<string, mixed>
     */
    public static function stored(): array
    {
        $app = app();
        if ($app->bound(self::MEMO)) {
            return (array) $app->make(self::MEMO);
        }

        $stored = self::fromCache();
        $app->instance(self::MEMO, $stored);

        return $stored;
    }

    public static function ready(): bool
    {
        try {
            return Schema::connection(self::CONNECTION)->hasTable(self::TABLE);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * ¿Se muestra este código de alerta? Los códigos desconocidos, sí.
     */
    public static function alertEnabled(string $code): bool
    {
        $enabled = (array) config('helpdeskErp.chat_alerts', []);

        foreach (self::ALERT_CODES as $type => $codes) {
            if (in_array($code, $codes, true)) {
                return (bool) ($enabled[$type] ?? true);
            }
        }

        return true;
    }

    /**
     * Da forma segura a un valor leído de la tabla o del formulario.
     * null = no tiene la forma esperada (vale el default).
     */
    public static function normalize(string $key, mixed $value): mixed
    {
        if (str_starts_with($key, 'chat_ttl.')) {
            return self::intIn($value, 0, 86400);
        }

        switch ($key) {
            case 'overview.orders_limit':
                return self::intIn($value, 1, 100);

            case 'alerts.expiry_days':
                return self::intIn($value, 1, 90);

            case 'metrics.retention_days':
                return self::intIn($value, 7, 730);

            case 'linking.auto':
            case 'metrics.enabled':
                return is_bool($value) ? $value : (in_array($value, [0, 1, '0', '1'], true) ? (bool) (int) $value : null);

            case 'alerts.enabled':
                if (! is_array($value)) {
                    return null;
                }
                $out = [];
                foreach (array_keys(self::ALERT_CODES) as $type) {
                    $out[$type] = array_key_exists($type, $value) ? (bool) $value[$type] : true;
                }

                return $out;

            case 'tracking.urls':
                if (! is_array($value)) {
                    return null;
                }
                $out = [];
                foreach ($value as $carrier => $template) {
                    $carrier = self::carrierKey((string) $carrier);
                    $template = is_string($template) ? trim($template) : '';
                    if ($carrier === '' || strlen($carrier) > 60 || ! self::validTrackingTemplate($template)) {
                        continue;
                    }
                    $out[$carrier] = $template;
                }

                // Una lista vacía guardada a propósito es válida.
                return $out;
        }

        return null;
    }

    /**
     * Clave de plantilla como la usa ErpChatResponseNormalizer::carrierKey():
     * minúsculas y solo letras y números ("Correos Express" → correosexpress).
     */
    public static function carrierKey(string $carrier): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', strtolower(Str::ascii(trim($carrier))));
    }

    /**
     * Un transportista añadido desde la pantalla tiene que poder reconocerse
     * por su nombre: su clave entra en helpdeskErp.tracking.aliases si no
     * estaba (los alias de config/.env se respetan).
     */
    private static function syncTrackingAliases(): void
    {
        $templates = (array) config('helpdeskErp.tracking.templates', []);
        $aliases = (array) config('helpdeskErp.tracking.aliases', []);
        $changed = false;

        foreach (array_keys($templates) as $key) {
            if (is_string($key) && $key !== '' && ! isset($aliases[$key])) {
                $aliases[$key] = $key;
                $changed = true;
            }
        }

        if ($changed) {
            config(['helpdeskErp.tracking.aliases' => $aliases]);
        }
    }

    public static function validTrackingTemplate(string $template): bool
    {
        return mb_strlen($template) <= 500
            && preg_match('~^https?://[^\s{}]+~i', $template) === 1
            && str_contains($template, '{tracking}');
    }

    private static function intIn(mixed $value, int $min, int $max): ?int
    {
        if (is_int($value) || (is_string($value) && preg_match('/^\d+$/', $value) === 1) || (is_float($value) && floor($value) === $value)) {
            $n = (int) $value;

            return $n >= $min && $n <= $max ? $n : null;
        }

        return null;
    }

    private static function captureDefaults(): void
    {
        if (config(self::DEFAULTS_KEY) !== null) {
            return;
        }

        $defaults = [];
        foreach (self::MAP as $key => $configKey) {
            $defaults[$key] = config($configKey);
        }

        // Valores de serie si el config no los trae.
        $defaults['tracking.urls'] = is_array($defaults['tracking.urls']) ? $defaults['tracking.urls'] : [];
        $defaults['linking.auto'] = $defaults['linking.auto'] === null ? true : (bool) $defaults['linking.auto'];
        $defaults['metrics.enabled'] = $defaults['metrics.enabled'] === null ? true : (bool) $defaults['metrics.enabled'];
        $defaults['alerts.enabled'] = self::normalize('alerts.enabled', (array) $defaults['alerts.enabled']);
        foreach (self::TTL_STATES as $state) {
            $defaults['chat_ttl.'.$state] = (int) $defaults['chat_ttl.'.$state];
        }
        $defaults['overview.orders_limit'] = (int) ($defaults['overview.orders_limit'] ?? 10);
        $defaults['alerts.expiry_days'] = (int) ($defaults['alerts.expiry_days'] ?? 7);
        $defaults['metrics.retention_days'] = (int) ($defaults['metrics.retention_days'] ?? 90);

        config([self::DEFAULTS_KEY => $defaults]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function fromCache(): array
    {
        try {
            $cached = Cache::get(self::CACHE_KEY);
        } catch (\Throwable) {
            $cached = null;
        }

        if (is_array($cached) && array_key_exists('values', $cached)) {
            return (array) $cached['values'];
        }

        $values = self::readTable();
        $ready = $values !== null;

        try {
            Cache::put(
                self::CACHE_KEY,
                ['ready' => $ready, 'values' => $values ?? []],
                $ready ? (int) config('helpdeskErp.ext.admin.cache_ttl', 3600) : self::NOT_READY_TTL,
            );
        } catch (\Throwable) {
            // Sin caché: se volverá a leer en la siguiente petición.
        }

        return $values ?? [];
    }

    /**
     * @return array<string, mixed>|null null = la tabla no existe todavía
     */
    private static function readTable(): ?array
    {
        if (! self::ready()) {
            return null;
        }

        $values = [];
        foreach (ErpAdminSetting::query()->get(['key', 'value']) as $row) {
            if (isset(self::MAP[$row->key])) {
                $values[$row->key] = $row->value;
            }
        }

        return $values;
    }
}

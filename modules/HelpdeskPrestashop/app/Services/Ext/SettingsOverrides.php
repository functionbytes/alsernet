<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aplica sobre config() los ajustes guardados en «Ajustes del chat»
 * (tabla helpdesk_ps_settings). El provider la llama en cada boot(), así que
 * tiene que ser barata:
 *
 *  - la lectura de la tabla se guarda en la caché de Laravel (una sola clave,
 *    solo con lo guardado, nunca con defaults: nada de repetir el problema de
 *    Setting::get, que cacheaba el default del primer llamador) y se
 *    memoriza en el contenedor para el resto de la petición;
 *  - si la tabla aún no existe (migración pendiente) se cachea «vacío» unos
 *    minutos para no preguntar al esquema en cada petición;
 *  - guardar desde la pantalla llama a refresh(), que invalida la caché.
 *
 * Antes de sobrescribir nada se copian los valores de config/.env en
 * config('helpdeskprestashop.ext.settings.defaults'): son los valores por
 * defecto que muestra la pantalla y a los que vuelve «Restablecer».
 */
final class SettingsOverrides
{
    public const TABLE = 'helpdesk_ps_settings';

    public const CONNECTION = 'helpdesk';

    public const CACHE_KEY = 'helpdeskprestashop:ps_settings:v1';

    /** Clave en el contenedor para memorizar lo leído durante la petición. */
    private const MEMO = 'helpdeskprestashop.ps_settings.stored';

    /** Donde se guardan los valores de config/.env antes de sobrescribirlos. */
    private const DEFAULTS_KEY = 'helpdeskprestashop.ext.settings.defaults';

    /** Segundos que se recuerda «la tabla no existe todavía». */
    private const NOT_READY_TTL = 300;

    /**
     * Clave lógica del ajuste (la de la tabla) → clave de config que pisa.
     */
    public const MAP = [
        'vouchers.agent_limit' => 'helpdeskprestashop.vouchers.agent_limit',
        'vouchers.approver_limit' => 'helpdeskprestashop.vouchers.approver_limit',
        'vouchers.validity_days' => 'helpdeskprestashop.vouchers.validity_days',
        'vouchers.reasons' => 'helpdeskprestashop.vouchers.reasons',
        'refunds.agent_limit' => 'helpdeskprestashop.ext.refunds.agent_limit',
        'refunds.approver_limit' => 'helpdeskprestashop.ext.refunds.approver_limit',
        'refunds.return_instructions' => 'helpdeskprestashop.ext.refunds.return_instructions',
        'quick_replies' => 'helpdeskprestashop.ext.settings.quick_replies',
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
    }

    /**
     * Invalida la caché y vuelve a aplicar leyendo directamente de la tabla.
     * No repuebla la caché: la siguiente petición lo hará (así una escritura
     * dentro de una transacción de test no se queda en la caché compartida).
     */
    public static function refresh(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable) {
            // Sin caché disponible no hay nada que invalidar.
        }

        self::captureDefaults();

        // Vuelta a los defaults antes de aplicar: una clave restablecida
        // (fila borrada) tiene que dejar de estar pisada en esta petición.
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
    }

    /**
     * Valores de config/.env, tal y como estaban antes de aplicar la tabla.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        self::captureDefaults();

        return (array) config(self::DEFAULTS_KEY, []);
    }

    /**
     * Ajustes guardados (solo los que difieren del default).
     *
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
     * Da forma segura a un valor leído de la tabla. Devuelve null si la
     * fila no tiene la forma esperada (se ignora y vale el default).
     */
    public static function normalize(string $key, mixed $value): mixed
    {
        switch ($key) {
            case 'vouchers.agent_limit':
            case 'vouchers.approver_limit':
            case 'refunds.agent_limit':
            case 'refunds.approver_limit':
                return is_numeric($value) && (float) $value > 0 ? round((float) $value, 2) : null;

            case 'vouchers.validity_days':
                if (! is_array($value)) {
                    return null;
                }
                $days = array_values(array_unique(array_filter(array_map('intval', $value), fn ($d) => $d > 0 && $d <= 365)));
                sort($days);

                return $days === [] ? null : $days;

            case 'vouchers.reasons':
                if (! is_array($value)) {
                    return null;
                }
                $reasons = [];
                foreach ($value as $k => $label) {
                    if (is_string($k) && $k !== '' && is_scalar($label) && trim((string) $label) !== '') {
                        $reasons[$k] = trim((string) $label);
                    }
                }

                return $reasons === [] ? null : $reasons;

            case 'refunds.return_instructions':
                if (! is_array($value)) {
                    return null;
                }
                $base = (array) (self::defaults()[$key] ?? []);

                return array_replace($base, array_filter([
                    'address' => isset($value['address']) ? (string) $value['address'] : null,
                    'carrier' => isset($value['carrier']) ? (string) $value['carrier'] : null,
                    'validity_days' => isset($value['validity_days']) ? max(1, (int) $value['validity_days']) : null,
                    'steps' => isset($value['steps']) && is_array($value['steps'])
                        ? array_values(array_filter(array_map(fn ($s) => trim((string) $s), $value['steps']), 'strlen'))
                        : null,
                ], fn ($v) => $v !== null));

            case 'quick_replies':
                if (! is_array($value)) {
                    return null;
                }
                $replies = [];
                foreach ($value as $row) {
                    if (is_array($row) && trim((string) ($row['t'] ?? '')) !== '' && trim((string) ($row['text'] ?? '')) !== '') {
                        $replies[] = [
                            't' => trim((string) $row['t']),
                            's' => trim((string) ($row['s'] ?? '')),
                            'text' => trim((string) $row['text']),
                        ];
                    }
                }

                // Una lista vacía guardada a propósito es válida: sin respuestas.
                return $replies;
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
                $ready ? (int) config('helpdeskprestashop.ext.settings.cache_ttl', 3600) : self::NOT_READY_TTL,
            );
        } catch (\Throwable) {
            // Sin caché: se volverá a leer en la siguiente petición.
        }

        return $values ?? [];
    }

    /**
     * Lee la tabla. null = la tabla no existe (migración pendiente).
     *
     * @return array<string, mixed>|null
     */
    private static function readTable(): ?array
    {
        if (! self::ready()) {
            return null;
        }

        $values = [];
        $rows = DB::connection(self::CONNECTION)->table(self::TABLE)->get(['key', 'value']);

        foreach ($rows as $row) {
            if (! isset(self::MAP[$row->key])) {
                continue;
            }
            $decoded = json_decode((string) $row->value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $values[$row->key] = $decoded;
            }
        }

        return $values;
    }
}

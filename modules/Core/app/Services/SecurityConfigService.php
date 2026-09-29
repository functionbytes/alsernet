<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;

/**
 * "Configuración de seguridad" (29-sep-2026, H4).
 *
 * Opciones de seguridad que hasta ahora solo se cambiaban en .env/config y que
 * ahora se pueden sobrescribir desde el panel (solo super-admin). Cada valor se
 * guarda en `settings` con la clave "security_config.<id>" y se aplica en
 * runtime con config([...]) desde CoreServiceProvider:
 *
 *  - Sin fila en settings manda el valor de config/.env de siempre: desplegar
 *    esto no cambia nada. Al guardar un valor igual al de .env se borra la fila.
 *  - Las filas se leen de una sola vez y se cachean (CACHE_KEY), invalidada al
 *    guardar. Los workers de cola las reaplican al empezar cada job.
 *  - El secreto HMAC se guarda cifrado (Setting::setEncrypted) y nunca se
 *    muestra ni se registra en claro.
 *  - AUTH_STAFF_IP_FILTER_FORCE_OFF no se gestiona aquí: sigue mandando el .env.
 */
class SecurityConfigService
{
    public const SETTING_PREFIX = 'security_config.';

    public const CACHE_KEY = 'security_config.overrides.v1';

    public const CACHE_TTL = 3600;

    /** Valor que se aplica al activar X-Robots-Tag si config no trae uno. */
    public const DEFAULT_ROBOTS_TAG = 'noindex, nofollow, noarchive, nosnippet';

    /**
     * id => [config, tipo, env, sección, etiqueta, (min, max | opciones)].
     *
     * Tipos: bool, int, enum, roles (lista), hosts (lista), ip_csv (cadena con
     * comas, como la lee ApiAuth), robots (cadena o vacío) y secret.
     */
    public const DEFINITIONS = [
        'two_factor_enabled' => ['config' => 'auth.auth-policy.two_factor_enforcement.enabled', 'type' => 'bool', 'env' => 'AUTH_REQUIRE_2FA', 'section' => 'access', 'label' => '2FA obligatorio'],
        'two_factor_roles' => ['config' => 'auth.auth-policy.two_factor_enforcement.roles', 'type' => 'roles', 'env' => 'AUTH_REQUIRE_2FA_ROLES', 'section' => 'access', 'label' => 'Roles con 2FA obligatorio'],

        'csp_enabled' => ['config' => 'security.csp.enabled', 'type' => 'bool', 'env' => 'SECURITY_CSP_ENABLED', 'section' => 'headers', 'label' => 'CSP activada'],
        'csp_report_only' => ['config' => 'security.csp.report_only', 'type' => 'bool', 'env' => 'SECURITY_CSP_REPORT_ONLY', 'section' => 'headers', 'label' => 'CSP solo informe'],
        'robots_tag' => ['config' => 'security.headers.robots_tag', 'type' => 'robots', 'env' => 'SECURITY_X_ROBOTS_TAG', 'section' => 'headers', 'label' => 'X-Robots-Tag'],

        'watch_failed_logins' => ['config' => 'security.watch.thresholds.failed_logins', 'type' => 'int', 'min' => 1, 'max' => 100000, 'env' => 'SECURITY_WATCH_FAILED_LOGINS', 'section' => 'watch', 'label' => 'Logins fallidos (total)'],
        'watch_failed_logins_ip' => ['config' => 'security.watch.thresholds.failed_logins_per_ip', 'type' => 'int', 'min' => 1, 'max' => 100000, 'env' => 'SECURITY_WATCH_FAILED_LOGINS_IP', 'section' => 'watch', 'label' => 'Logins fallidos por IP'],
        'watch_failed_logins_email' => ['config' => 'security.watch.thresholds.failed_logins_per_email', 'type' => 'int', 'min' => 1, 'max' => 100000, 'env' => 'SECURITY_WATCH_FAILED_LOGINS_EMAIL', 'section' => 'watch', 'label' => 'Logins fallidos por cuenta'],
        'watch_erp_403' => ['config' => 'security.watch.thresholds.erp_denied_403', 'type' => 'int', 'min' => 1, 'max' => 100000, 'env' => 'SECURITY_WATCH_ERP_403', 'section' => 'watch', 'label' => 'API ERP: 403 (IP no permitida)'],
        'watch_erp_401' => ['config' => 'security.watch.thresholds.erp_denied_401', 'type' => 'int', 'min' => 1, 'max' => 100000, 'env' => 'SECURITY_WATCH_ERP_401', 'section' => 'watch', 'label' => 'API ERP: 401 (credenciales)'],

        'erp_allowed_ips' => ['config' => 'erp.api.allowed_ips', 'type' => 'ip_csv', 'env' => 'ERP_API_ALLOWED_IPS', 'section' => 'erp', 'label' => 'IPs permitidas en /api/erp'],

        'documents_require_signed' => ['config' => 'documents.require_signed_server_requests', 'type' => 'bool', 'env' => 'DOCUMENTS_REQUIRE_SIGNED_SERVER_REQUESTS', 'section' => 'documents', 'label' => 'Exigir firma a la tienda'],
        'documents_prestashop_secret' => ['config' => 'documents.webhooks.prestashop_secret', 'type' => 'secret', 'env' => 'DOCUMENTS_PRESTASHOP_WEBHOOK_SECRET', 'section' => 'documents', 'label' => 'Secreto HMAC de PrestaShop'],
        'documents_signed_url_minutes' => ['config' => 'documents.signed_media_url_minutes', 'type' => 'int', 'min' => 5, 'max' => 1440, 'env' => 'DOCUMENTS_SIGNED_MEDIA_URL_MINUTES', 'section' => 'documents', 'label' => 'Validez de URLs firmadas (min)'],

        'helpdesk_attachments_disk' => ['config' => 'helpdesk.attachments.disk', 'type' => 'enum', 'options' => ['public', 'local'], 'env' => 'HELPDESK_ATTACHMENTS_DISK', 'section' => 'helpdesk', 'label' => 'Disco de adjuntos del Helpdesk'],

        'mcp_server_enabled' => ['config' => 'helpdeskagents.mcp.server_enabled', 'type' => 'bool', 'env' => 'HELPDESKAGENTS_MCP_SERVER', 'section' => 'ai', 'label' => 'Servidor MCP HTTP'],
        'local_llm_allowed_hosts' => ['config' => 'helpdeskagents.local_llm_allowed_hosts', 'type' => 'hosts', 'env' => 'HELPDESKAGENTS_LOCAL_LLM_ALLOWED_HOSTS', 'section' => 'ai', 'label' => 'Hosts permitidos del LLM local'],

        'supplier_erp_allowed_hosts' => ['config' => 'supplier.erp_allowed_hosts', 'type' => 'hosts', 'env' => 'SUPPLIER_ERP_ALLOWED_HOSTS', 'section' => 'supplier', 'label' => 'Hosts ERP permitidos (Proveedores)'],
    ];

    /** Valores de config antes de aplicar ningún ajuste (los de .env/config). */
    private static array $originals = [];

    // ------------------------------------------------------------------
    // Aplicación en runtime
    // ------------------------------------------------------------------

    /**
     * Aplica los ajustes guardados sobre config(). Se llama tras el arranque de
     * todos los providers y al empezar cada job de cola. Nunca lanza: si no hay
     * BD/caché se queda la config de .env.
     */
    public static function apply(): void
    {
        $overrides = self::overrides();

        foreach (self::DEFINITIONS as $id => $def) {
            $path = $def['config'];
            if (! array_key_exists($path, self::$originals)) {
                self::$originals[$path] = config($path);
            }

            if (array_key_exists($id, $overrides)) {
                config([$path => $overrides[$id]]);
            } elseif (config($path) !== self::$originals[$path]) {
                // Se borró el ajuste: vuelve el valor de .env (workers de larga vida).
                config([$path => self::$originals[$path]]);
            }
        }
    }

    /**
     * routes/ai.php decide si publica el servidor MCP al cargar las rutas, antes
     * de que HelpdeskAgents fusione su config. Si hay ajuste, se deja la config
     * del módulo ya fusionada con el valor guardado antes de que arranque ningún
     * provider (mergeConfigFrom respeta lo que ya existe).
     */
    public static function applyEarly(): void
    {
        $overrides = self::overrides();
        if (! array_key_exists('mcp_server_enabled', $overrides)) {
            return;
        }

        $value = (bool) $overrides['mcp_server_enabled'];

        try {
            $current = config('helpdeskagents');
            if (is_array($current) && $current !== []) {
                self::$originals['helpdeskagents.mcp.server_enabled'] ??= config('helpdeskagents.mcp.server_enabled');
                config(['helpdeskagents.mcp.server_enabled' => $value]);

                return;
            }

            $file = function_exists('module_path') ? module_path('HelpdeskAgents', 'config/config.php') : null;
            if ($file && is_file($file)) {
                $base = require $file;
                if (is_array($base)) {
                    self::$originals['helpdeskagents.mcp.server_enabled'] ??= $base['mcp']['server_enabled'] ?? null;
                    $base['mcp'] = array_merge((array) ($base['mcp'] ?? []), ['server_enabled' => $value]);
                    config(['helpdeskagents' => $base]);
                }
            }
        } catch (Throwable $e) {
            Log::warning('security_config: no se pudo aplicar mcp_server_enabled antes de las rutas: '.$e->getMessage());
        }
    }

    /**
     * Valores guardados (id => valor ya tipado), cacheados.
     *
     * @return array<string, mixed>
     */
    public static function overrides(): array
    {
        try {
            $raw = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => self::loadRows());
        } catch (Throwable) {
            // Caché caída: se lee directamente de la BD.
            try {
                $raw = self::loadRows();
            } catch (Throwable) {
                return [];
            }
        }

        $out = [];
        foreach ((array) $raw as $id => $stored) {
            if (! isset(self::DEFINITIONS[$id])) {
                continue;
            }
            $decoded = self::decode($id, $stored);
            if ($decoded !== self::INVALID) {
                $out[$id] = $decoded;
            }
        }

        return $out;
    }

    private const INVALID = "\0invalid";

    /** @return array<string, string> id => valor almacenado (sin descifrar) */
    private static function loadRows(): array
    {
        // Query builder y no Eloquent: applyEarly() corre antes de que
        // DatabaseServiceProvider arranque Eloquent.
        $rows = DB::table((new Setting)->getTable())
            ->where('key', 'like', self::SETTING_PREFIX.'%')
            ->pluck('value', 'key')
            ->all();

        $out = [];
        foreach ($rows as $key => $value) {
            $out[substr((string) $key, strlen(self::SETTING_PREFIX))] = $value;
        }

        return $out;
    }

    private static function decode(string $id, mixed $stored): mixed
    {
        $type = self::DEFINITIONS[$id]['type'];

        if ($type === 'secret') {
            if (! is_string($stored) || $stored === '') {
                return self::INVALID;
            }
            if (! str_starts_with($stored, 'enc:')) {
                return $stored;
            }
            try {
                return Crypt::decryptString(substr($stored, 4));
            } catch (Throwable) {
                // APP_KEY rotada: se ignora la fila y manda el .env.
                return self::INVALID;
            }
        }

        $value = json_decode((string) $stored, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return self::INVALID;
        }

        return match ($type) {
            'bool' => is_bool($value) ? $value : self::INVALID,
            'int' => is_int($value) ? $value : self::INVALID,
            'enum' => in_array($value, self::DEFINITIONS[$id]['options'], true) ? $value : self::INVALID,
            'roles', 'hosts' => is_array($value) ? array_values(array_map('strval', $value)) : self::INVALID,
            'ip_csv' => is_string($value) && $value !== '' ? $value : self::INVALID,
            'robots' => $value === null || is_string($value) ? $value : self::INVALID,
            default => self::INVALID,
        };
    }

    // ------------------------------------------------------------------
    // Lectura para la pantalla
    // ------------------------------------------------------------------

    /** Valor de .env/config (sin ajustes). */
    public static function original(string $id): mixed
    {
        $path = self::DEFINITIONS[$id]['config'];

        return array_key_exists($path, self::$originals) ? self::$originals[$path] : config($path);
    }

    /** Valor efectivo ahora mismo. */
    public static function effective(string $id): mixed
    {
        return config(self::DEFINITIONS[$id]['config']);
    }

    public static function hasOverride(string $id): bool
    {
        return array_key_exists($id, self::overrides());
    }

    public static function sectionIds(string $section): array
    {
        return array_keys(array_filter(self::DEFINITIONS, fn ($d) => $d['section'] === $section));
    }

    // ------------------------------------------------------------------
    // Escritura
    // ------------------------------------------------------------------

    /**
     * Guarda los valores (id => valor tipado) que cambian respecto al efectivo.
     * Un valor igual al de .env borra la fila (vuelve a mandar .env).
     *
     * @return array<int, array{id: string, old: string, new: string}> cambios hechos
     */
    public static function save(array $values, $actor, ?string $ip): array
    {
        $changes = [];

        foreach ($values as $id => $new) {
            if (! isset(self::DEFINITIONS[$id])) {
                continue;
            }
            $def = self::DEFINITIONS[$id];
            $old = self::effective($id);

            if (self::same($old, $new)) {
                continue;
            }

            $key = self::SETTING_PREFIX.$id;
            if (self::same(self::original($id), $new) && $def['type'] !== 'secret') {
                Setting::query()->where('key', $key)->delete();
                cache()->forget("setting_{$key}");
            } elseif ($def['type'] === 'secret') {
                Setting::setEncrypted($key, (string) $new);
            } else {
                Setting::set($key, json_encode($new, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }

            $changes[] = ['id' => $id, 'old' => self::display($id, $old), 'new' => self::display($id, $new)];
        }

        if ($changes !== []) {
            self::flush();
            foreach ($changes as $change) {
                self::audit($actor, $ip, 'security_config_updated', $change);
            }
        }

        return $changes;
    }

    /** Borra el ajuste guardado: vuelve a mandar el valor de .env/config. */
    public static function reset(string $id, $actor, ?string $ip): bool
    {
        if (! isset(self::DEFINITIONS[$id]) || ! self::hasOverride($id)) {
            return false;
        }

        $old = self::effective($id);
        $key = self::SETTING_PREFIX.$id;
        Setting::query()->where('key', $key)->delete();
        cache()->forget("setting_{$key}");
        self::flush();

        self::audit($actor, $ip, 'security_config_reset', [
            'id' => $id,
            'old' => self::display($id, $old),
            'new' => self::display($id, self::effective($id)).' (valor de .env)',
        ]);

        return true;
    }

    /** Invalida la caché y reaplica en este proceso. */
    public static function flush(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // sin caché no hay nada que invalidar
        }
        self::apply();
    }

    private static function same(mixed $a, mixed $b): bool
    {
        if (is_array($a) || is_array($b)) {
            // Listas: el orden no importa (roles/hosts).
            $a = array_map('strval', array_values((array) $a));
            $b = array_map('strval', array_values((array) $b));
            sort($a);
            sort($b);

            return $a === $b;
        }
        if (is_bool($a) || is_bool($b)) {
            return (bool) $a === (bool) $b;
        }
        if (is_int($a) || is_int($b)) {
            return (string) $a === (string) $b;
        }

        return (string) $a === (string) $b;
    }

    /** Texto para el log de actividad y la pantalla. Los secretos nunca en claro. */
    public static function display(string $id, mixed $value): string
    {
        return match (self::DEFINITIONS[$id]['type']) {
            'secret' => filled($value) ? '(configurado)' : '(no configurado)',
            'bool' => $value ? 'sí' : 'no',
            'roles', 'hosts' => $value === [] || $value === null ? '(vacío)' : implode(', ', (array) $value),
            'robots' => filled($value) ? (string) $value : '(desactivado)',
            default => (string) $value,
        };
    }

    private static function audit($actor, ?string $ip, string $event, array $change): void
    {
        $def = self::DEFINITIONS[$change['id']];

        try {
            activity()
                ->causedBy($actor)
                ->event($event)
                ->withProperties([
                    'key' => $change['id'],
                    'config' => $def['config'],
                    'env' => $def['env'],
                    'old' => $change['old'],
                    'new' => $change['new'],
                    'ip' => $ip,
                ])
                ->log("Configuración de seguridad: {$def['label']}: {$change['old']} → {$change['new']}");
        } catch (Throwable) {
            // El registro de actividad no debe impedir el cambio.
        }

        Log::channel('security')->info('security_config: '.$event, [
            'user_id' => $actor?->id,
            'key' => $change['id'],
            'old' => $change['old'],
            'new' => $change['new'],
            'ip' => $ip,
        ]);
    }

    // ------------------------------------------------------------------
    // Validación
    // ------------------------------------------------------------------

    /** Una IP o un rango CIDR (IPv4/IPv6) válido. */
    public static function isValidIpOrCidr(string $entry): bool
    {
        $entry = trim($entry);
        if ($entry === '') {
            return false;
        }

        [$ip, $mask] = str_contains($entry, '/') ? explode('/', $entry, 2) : [$entry, null];
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        if ($mask !== null) {
            $max = str_contains($ip, ':') ? 128 : 32;
            if (! ctype_digit($mask) || (int) $mask < 0 || (int) $mask > $max) {
                return false;
            }
        }

        // Misma librería que usa ApiAuth: si no la acepta, no sirve.
        try {
            IpUtils::checkIp($ip, $entry);
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /** Nombre de host o IP (sin esquema, puerto ni ruta). */
    public static function isValidHost(string $host): bool
    {
        $host = trim($host);
        if ($host === '' || strlen($host) > 253) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        return (bool) preg_match('/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))*$/i', $host);
    }

    /** Separa una lista escrita por comas, espacios o líneas. */
    public static function splitList(?string $text): array
    {
        $parts = preg_split('/[\s,;]+/', (string) $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_map('trim', $parts)));
    }
}

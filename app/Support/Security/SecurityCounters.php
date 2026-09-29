<?php

namespace App\Support\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Contadores por minuto de eventos de seguridad (29-sep-2026) que lee
 * `php artisan security:watch`. Viven en el store "limiter" (Redis propio), que
 * `cache:clear` no vacía. Cada cubo caduca a las 2 h.
 *
 * Métricas:
 *  - erp_denied_403 / erp_denied_401: log "ERP API: acceso denegado" de
 *    Modules\Erp\Http\Middleware\ApiAuth (LOG_CHANNEL=stderr no deja fichero
 *    legible, por eso se cuentan al vuelo con el evento MessageLogged).
 *  - failed_logins: evento Illuminate\Auth\Events\Failed/Lockout (respaldo de
 *    la tabla login_attempts).
 */
class SecurityCounters
{
    public const ERP_DENIED_MESSAGE = 'ERP API: acceso denegado';

    private const PREFIX = 'security-watch:';

    private const TTL_SECONDS = 7200;

    /** Nº máximo de denegaciones ERP por minuto que se copian al log "security". */
    private const LOG_SAMPLE_PER_MINUTE = 20;

    public static function increment(string $metric, ?int $timestamp = null): int
    {
        try {
            $key = self::key($metric, $timestamp ?? time());
            $store = Cache::store(self::storeName());
            $value = (int) $store->increment($key);
            if ($value === 1) {
                // Primer incremento del cubo: fijar caducidad.
                $store->put($key, 1, self::TTL_SECONDS);
            }

            return $value;
        } catch (Throwable) {
            return 0;
        }
    }

    /** Suma de los últimos $minutes cubos (incluido el minuto actual). */
    public static function sum(string $metric, int $minutes, ?int $now = null): int
    {
        $now ??= time();
        $keys = [];
        for ($i = 0; $i < $minutes; $i++) {
            $keys[] = self::key($metric, $now - $i * 60);
        }

        try {
            return (int) array_sum(array_map('intval', Cache::store(self::storeName())->many($keys)));
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Listener de Illuminate\Log\Events\MessageLogged: cuenta las denegaciones
     * de la API ERP y deja una muestra en el log "security".
     */
    public static function onMessageLogged(object $event): void
    {
        if (($event->message ?? null) !== self::ERP_DENIED_MESSAGE) {
            return;
        }

        $context = is_array($event->context ?? null) ? $event->context : [];
        $metric = ($context['reason'] ?? '') === 'ip' ? 'erp_denied_403' : 'erp_denied_401';
        $count = self::increment($metric);

        if ($count > 0 && $count <= self::LOG_SAMPLE_PER_MINUTE) {
            try {
                // Mensaje distinto al original: este log también dispara MessageLogged.
                Log::channel('security')->warning('ERP API: denegación', [
                    'status' => $metric === 'erp_denied_403' ? 403 : 401,
                    'reason' => $context['reason'] ?? null,
                    'ip' => $context['ip'] ?? null,
                    'method' => $context['method'] ?? null,
                    'path' => $context['path'] ?? null,
                ]);
            } catch (Throwable) {
                // Nunca romper la petición por el log de seguridad.
            }
        }
    }

    private static function key(string $metric, int $timestamp): string
    {
        return self::PREFIX.$metric.':'.gmdate('YmdHi', $timestamp);
    }

    private static function storeName(): string
    {
        return (string) config('security.watch.counter_store', 'limiter');
    }
}

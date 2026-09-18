<?php

namespace Modules\HelpdeskTickets\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Claves de caché versionadas para reports/dashboard de tickets.
 *
 * Antes el observer hacía Cache::forget('helpdesk:reports'), pero las claves
 * reales llevan sufijos dinámicos (helpdesk:reports:index:{from}:{to},
 * helpdesk:dashboard:*, ...), así que nunca se invalidaba nada y los informes
 * quedaban obsoletos hasta expirar el TTL.
 *
 * En lugar de borrar claves (imposible sin Cache::tags, que no soportan todos
 * los stores), se incorpora un número de versión a cada clave. Al escribir un
 * ticket se incrementa la versión (bump), con lo que todas las entradas
 * antiguas dejan de referenciarse y expiran solas por TTL.
 */
class ReportsCache
{
    private const VERSION_KEY = 'helpdesk:reports:ver';

    /**
     * Versión actual de los datos de reports/dashboard.
     */
    public static function version(): int
    {
        return (int) (Cache::get(self::VERSION_KEY) ?? 1);
    }

    /**
     * Clave versionada para cachés de reports.
     */
    public static function key(string $suffix): string
    {
        return 'helpdesk:reports:v'.self::version().':'.$suffix;
    }

    /**
     * Clave versionada para cachés del dashboard.
     */
    public static function dashboardKey(string $suffix): string
    {
        return 'helpdesk:dashboard:v'.self::version().':'.$suffix;
    }

    /**
     * Invalida todos los cachés de reports/dashboard incrementando la versión.
     * add() siembra el contador de forma atómica la primera vez; después,
     * increment() es atómico en todos los stores (file, database, redis...).
     */
    public static function bump(): void
    {
        if (! Cache::add(self::VERSION_KEY, 2)) {
            Cache::increment(self::VERSION_KEY);
        }
    }
}

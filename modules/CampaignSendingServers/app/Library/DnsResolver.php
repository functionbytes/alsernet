<?php

namespace Modules\CampaignSendingServers\Library;

/**
 * Wrapper sobre dns_get_record() con cache corto en memoria por proceso,
 * timeout y manejo de errores. Usado por verificación de TrackingDomain
 * y SendingDomain para evitar llamadas duplicadas dentro de una misma
 * ejecución de cron.
 */
class DnsResolver
{
    /** @var array<string,array<string,array<int,array>>> */
    protected static array $cache = [];

    /**
     * Resolver registros DNS de un tipo específico (A|CNAME|TXT|MX|...).
     *
     * @return array<int,array> Lista de registros (vacío si no hay).
     */
    public static function resolve(string $hostname, string $type): array
    {
        $hostname = strtolower(trim($hostname, '.'));
        $type = strtoupper($type);

        if (isset(self::$cache[$hostname][$type])) {
            return self::$cache[$hostname][$type];
        }

        $constant = constant('DNS_'.$type) ?? DNS_ANY;
        $records = @dns_get_record($hostname, $constant) ?: [];

        return self::$cache[$hostname][$type] = $records;
    }

    /**
     * Devuelve los targets de los registros CNAME para un host (vacío si no hay).
     *
     * @return string[]
     */
    public static function cname(string $hostname): array
    {
        $records = self::resolve($hostname, 'CNAME');

        return array_values(array_filter(array_map(
            fn ($r) => isset($r['target']) ? strtolower(rtrim($r['target'], '.')) : null,
            $records,
        )));
    }

    /**
     * Devuelve las IPs de los registros A para un host.
     *
     * @return string[]
     */
    public static function a(string $hostname): array
    {
        $records = self::resolve($hostname, 'A');

        return array_values(array_filter(array_map(
            fn ($r) => $r['ip'] ?? null,
            $records,
        )));
    }

    /**
     * Devuelve los textos de los registros TXT, concatenados (los TXT
     * largos vienen partidos en chunks de 255 chars).
     *
     * @return string[]
     */
    public static function txt(string $hostname): array
    {
        $records = self::resolve($hostname, 'TXT');

        return array_values(array_filter(array_map(function ($r) {
            if (! empty($r['entries']) && is_array($r['entries'])) {
                return implode('', $r['entries']);
            }

            return $r['txt'] ?? null;
        }, $records)));
    }

    /** Limpia la cache (uso en tests). */
    public static function flush(): void
    {
        self::$cache = [];
    }
}

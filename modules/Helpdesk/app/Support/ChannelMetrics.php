<?php

namespace Modules\Helpdesk\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Contadores ligeros del pipeline de canales (WhatsApp/Facebook/Instagram),
 * en buckets diarios por canal. Sirven para observabilidad básica (throughput
 * de entrada, fallos de envío) sin depender de infraestructura de métricas.
 *
 * Métricas: 'inbound' (mensajes ingeridos), 'send_failed' (envíos fallidos).
 */
class ChannelMetrics
{
    private const PREFIX = 'helpdesk:metrics:';

    private const RETENTION_DAYS = 8;

    public static function increment(string $metric, string $channel): void
    {
        $key = self::key(now()->format('Y-m-d'), $channel, $metric);

        // TTL solo la primera vez (add no reescribe si ya existe); increment después.
        Cache::add($key, 0, now()->addDays(self::RETENTION_DAYS));
        Cache::increment($key);
    }

    public static function get(string $date, string $channel, string $metric): int
    {
        return (int) Cache::get(self::key($date, $channel, $metric), 0);
    }

    /**
     * Snapshot de un día: [canal => [metrica => conteo]].
     *
     * @param  array<int, string>  $channels
     * @param  array<int, string>  $metrics
     * @return array<string, array<string, int>>
     */
    public static function snapshot(?string $date = null, array $channels = ['whatsapp', 'facebook', 'instagram'], array $metrics = ['inbound', 'send_failed']): array
    {
        $date ??= now()->format('Y-m-d');
        $out = [];

        foreach ($channels as $channel) {
            foreach ($metrics as $metric) {
                $out[$channel][$metric] = self::get($date, $channel, $metric);
            }
        }

        return $out;
    }

    private static function key(string $date, string $channel, string $metric): string
    {
        return self::PREFIX."{$date}:{$channel}:{$metric}";
    }
}

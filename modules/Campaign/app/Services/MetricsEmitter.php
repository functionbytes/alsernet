<?php

namespace Modules\Campaign\Services;

use Illuminate\Support\Facades\Log;

/**
 * Emite métricas en formato Prometheus/StatsD para observabilidad.
 * En producción se puede redirigir a un StatsD daemon o archivo de texto.
 */
class MetricsEmitter
{
    private static array $counters = [];

    private static array $gauges = [];

    private static array $histograms = [];

    public static function counter(string $name, float $value = 1, array $labels = []): void
    {
        $key = self::key($name, $labels);
        self::$counters[$key] = (self::$counters[$key] ?? 0) + $value;
        self::log('counter', $name, $value, $labels);
    }

    public static function gauge(string $name, float $value, array $labels = []): void
    {
        $key = self::key($name, $labels);
        self::$gauges[$key] = $value;
        self::log('gauge', $name, $value, $labels);
    }

    public static function histogram(string $name, float $value, array $labels = []): void
    {
        $key = self::key($name, $labels);
        self::$histograms[$key][] = $value;
        self::log('histogram', $name, $value, $labels);
    }

    public static function flush(): array
    {
        return [
            'counters' => self::$counters,
            'gauges' => self::$gauges,
            'histograms' => self::$histograms,
        ];
    }

    public static function reset(): void
    {
        self::$counters = [];
        self::$gauges = [];
        self::$histograms = [];
    }

    private static function key(string $name, array $labels): string
    {
        if (empty($labels)) {
            return $name;
        }

        ksort($labels);
        $parts = [];
        foreach ($labels as $k => $v) {
            $parts[] = "{$k}=\"{$v}\"";
        }

        return $name.'{'.implode(',', $parts).'}';
    }

    private static function log(string $type, string $name, float $value, array $labels): void
    {
        if (! config('campaign.metrics.enabled', false)) {
            return;
        }

        Log::channel(config('campaign.metrics.channel', 'default'))->info('campaign_metric', [
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'labels' => $labels,
        ]);
    }
}

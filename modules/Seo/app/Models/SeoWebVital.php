<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Individual Core Web Vitals sample sent from the browser. Each record holds
 * a single metric reading (LCP/INP/CLS/FCP/TTFB). Aggregation to p75 happens
 * on read, following Google's methodology.
 */
class SeoWebVital extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'seo_web_vitals';

    /**
     * Google thresholds (p75 value boundaries).
     *
     * @see https://web.dev/articles/vitals
     */
    public const THRESHOLDS = [
        'LCP' => ['good' => 2500, 'poor' => 4000],
        'INP' => ['good' => 200, 'poor' => 500],
        'CLS' => ['good' => 0.1, 'poor' => 0.25],
        'FCP' => ['good' => 1800, 'poor' => 3000],
        'TTFB' => ['good' => 800, 'poor' => 1800],
    ];

    /**
     * @var array<string>
     */
    protected $fillable = [
        'url',
        'url_path',
        'metric',
        'value',
        'rating',
        'device',
        'connection',
        'navigation_type',
        'captured_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:3',
        'captured_at' => 'datetime',
    ];

    public function scopeForMetric(Builder $query, string $metric): Builder
    {
        return $query->where('metric', strtoupper($metric));
    }

    public function scopeSince(Builder $query, \DateTimeInterface|string $since): Builder
    {
        return $query->where('captured_at', '>=', $since);
    }

    public function scopeOlderThan(Builder $query, int $days): Builder
    {
        return $query->where('captured_at', '<', now()->subDays($days));
    }

    public function scopeForUrlPath(Builder $query, string $path): Builder
    {
        return $query->where('url_path', $path);
    }

    /**
     * Classify a value according to Google's thresholds for a metric.
     */
    public static function rate(string $metric, float $value): string
    {
        $t = self::THRESHOLDS[$metric] ?? null;

        if (! $t) {
            return 'unknown';
        }

        if ($value <= $t['good']) {
            return 'good';
        }

        if ($value <= $t['poor']) {
            return 'needs-improvement';
        }

        return 'poor';
    }

    /**
     * Percentil 75 de una métrica para una URL (path) en los últimos N días.
     * Es la métrica oficial de Google para Core Web Vitals.
     */
    public static function p75(string $metric, ?string $urlPath = null, int $lastDays = 28): ?float
    {
        $query = static::query()
            ->forMetric($metric)
            ->since(now()->subDays($lastDays));

        if ($urlPath !== null) {
            $query->forUrlPath($urlPath);
        }

        $count = (int) $query->count();
        if ($count === 0) {
            return null;
        }

        $offset = max(0, min((int) floor($count * 0.75), $count - 1));

        $value = $query->orderBy('value')->offset($offset)->limit(1)->value('value');

        return $value === null ? null : (float) $value;
    }
}

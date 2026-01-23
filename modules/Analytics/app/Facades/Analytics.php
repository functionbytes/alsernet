<?php

namespace Modules\Analytics\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Modules\Analytics\Analytics dateRange(\Modules\Analytics\Period $period)
 * @method static \Modules\Analytics\Analytics metrics(string|array $metrics)
 * @method static \Modules\Analytics\Analytics dimensions(string|array $dimensions)
 * @method static \Modules\Analytics\Analytics orderByMetricDesc(string $metric)
 * @method static \Modules\Analytics\Analytics orderByMetricAsc(string $metric)
 * @method static \Modules\Analytics\Analytics orderByDimensionDesc(string $dimension)
 * @method static \Modules\Analytics\Analytics orderByDimensionAsc(string $dimension)
 * @method static \Modules\Analytics\Analytics whereDimension(string $dimension, string $operator, string|int $value)
 * @method static \Modules\Analytics\Analytics whereMetric(string $metric, string $operator, string|int $value)
 * @method static \Modules\Analytics\Analytics limit(int $limit)
 * @method static \Modules\Analytics\Analytics offset(int $offset)
 * @method static \Modules\Analytics\Analytics keepEmptyRows(bool $keep = true)
 * @method static \Modules\Analytics\AnalyticsResponse get()
 * @method static \Illuminate\Support\Collection fetchMostVisitedPages(\Modules\Analytics\Period $period, int $maxResults = 20)
 * @method static \Illuminate\Support\Collection fetchTopReferrers(\Modules\Analytics\Period $period, int $maxResults = 20)
 * @method static \Illuminate\Support\Collection fetchTopBrowsers(\Modules\Analytics\Period $period, int $maxResults = 10)
 * @method static \Illuminate\Support\Collection performQuery(\Modules\Analytics\Period $period, string|array $metrics, string|array $dimensions = [])
 * @method static void clearCache()
 */
class Analytics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'analytics';
    }
}

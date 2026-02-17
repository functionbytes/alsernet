<?php

namespace Modules\Analytics\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Analytics\Abstracts\AnalyticsAbstract;

/**
 * @method static string getCredentials()
 * @method static \Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient getClient()
 * @method static \Modules\Analytics\AnalyticsResponse get()
 * @method static \Illuminate\Support\Collection fetchMostVisitedPages(\Modules\Analytics\Period $period, int $maxResults = 20)
 * @method static \Illuminate\Support\Collection fetchTopReferrers(\Modules\Analytics\Period $period, int $maxResults = 20)
 * @method static \Illuminate\Support\Collection fetchTopBrowsers(\Modules\Analytics\Period $period, int $maxResults = 10)
 * @method static \Illuminate\Support\Collection performQuery(\Modules\Analytics\Period $period, array|string $metrics, array|string $dimensions = [])
 * @method static string getPropertyId()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static \Modules\Analytics\Analytics dateRange(\Modules\Analytics\Period $period)
 * @method static \Modules\Analytics\Analytics dateRanges(\Modules\Analytics\Period ...$items)
 * @method static \Modules\Analytics\Analytics metric(string $name)
 * @method static \Modules\Analytics\Analytics metrics(array|string $items)
 * @method static \Modules\Analytics\Analytics dimension(string $name)
 * @method static \Modules\Analytics\Analytics dimensions(array|string $items)
 * @method static \Modules\Analytics\Analytics orderByMetric(string $name, string $order = 'ASC')
 * @method static \Modules\Analytics\Analytics orderByMetricDesc(string $name)
 * @method static \Modules\Analytics\Analytics orderByDimension(string $name, string $order = 'ASC')
 * @method static \Modules\Analytics\Analytics orderByDimensionDesc(string $name)
 * @method static \Modules\Analytics\Analytics metricAggregation(int $value)
 * @method static \Modules\Analytics\Analytics metricAggregations(int ...$items)
 * @method static \Modules\Analytics\Analytics whereDimension(string $name, int $matchType, $value, bool $caseSensitive = false)
 * @method static \Modules\Analytics\Analytics whereDimensionIn(string $name, array $values, bool $caseSensitive = false)
 * @method static \Modules\Analytics\Analytics whereMetric(string $name, int $operation, $value)
 * @method static \Modules\Analytics\Analytics whereMetricBetween(string $name, $from, $to)
 * @method static \Modules\Analytics\Analytics keepEmptyRows(bool $keepEmptyRows = false)
 * @method static \Modules\Analytics\Analytics limit(int|null $limit = null)
 * @method static \Modules\Analytics\Analytics offset(int|null $offset = null)
 *
 * @see \Modules\Analytics\Analytics
 */
class Analytics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AnalyticsAbstract::class;
    }
}

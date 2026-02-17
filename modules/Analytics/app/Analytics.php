<?php

namespace Modules\Analytics;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Analytics\Abstracts\AnalyticsAbstract;
use Modules\Analytics\Abstracts\AnalyticsContract;
use Modules\Analytics\Exceptions\InvalidConfiguration;
use Modules\Analytics\Traits\DateRangeTrait;
use Modules\Analytics\Traits\DimensionTrait;
use Modules\Analytics\Traits\FilterByDimensionTrait;
use Modules\Analytics\Traits\FilterByMetricTrait;
use Modules\Analytics\Traits\MetricAggregationTrait;
use Modules\Analytics\Traits\MetricTrait;
use Modules\Analytics\Traits\OrderByDimensionTrait;
use Modules\Analytics\Traits\OrderByMetricTrait;
use Modules\Analytics\Traits\ResponseTrait;
use Modules\Analytics\Traits\RowOperationTrait;

class Analytics extends AnalyticsAbstract implements AnalyticsContract
{
    use DateRangeTrait;
    use DimensionTrait;
    use FilterByDimensionTrait;
    use FilterByMetricTrait;
    use MetricAggregationTrait;
    use MetricTrait;
    use OrderByDimensionTrait;
    use OrderByMetricTrait;
    use ResponseTrait;
    use RowOperationTrait;

    public array $orderBys = [];

    public function __construct(int|string $propertyId, string $credentials)
    {
        $this->propertyId = $propertyId;
        $this->credentials = $credentials;
    }

    public function getCredentials(): string
    {
        return $this->credentials;
    }

    public function getClient(): BetaAnalyticsDataClient
    {
        $storage = Storage::disk('local');

        $fileName = 'analytics-credentials.json';

        if (! $storage->exists($fileName) || md5_file($storage->path($fileName)) !== md5($this->getCredentials())) {
            $storage->put('analytics-credentials.json', $this->getCredentials());
        }

        if (! $storage->exists($fileName)) {
            throw new InvalidConfiguration('The credentials file does not exist.');
        }

        return new BetaAnalyticsDataClient([
            'credentials' => $storage->path($fileName),
        ]);
    }

    public function get(): AnalyticsResponse
    {
        $params = [
            'property' => 'properties/'.$this->getPropertyId(),
            'date_ranges' => $this->dateRanges,
            'metrics' => $this->metrics,
            'dimensions' => $this->dimensions,
        ];

        if (! empty($this->orderBys)) {
            $params['order_bys'] = $this->orderBys;
        }

        if (! empty($this->metricAggregations)) {
            $params['metric_aggregations'] = $this->metricAggregations;
        }

        if (! empty($this->dimensionFilter)) {
            $params['dimension_filter'] = $this->dimensionFilter;
        }

        if (! empty($this->metricFilter)) {
            $params['metric_filter'] = $this->metricFilter;
        }

        if (is_int($this->limit)) {
            $params['limit'] = $this->limit;
        }

        if (is_int($this->offset)) {
            $params['offset'] = $this->offset;
        }

        if (is_bool($this->keepEmptyRows)) {
            $params['keep_empty_rows'] = $this->keepEmptyRows;
        }

        $request = new RunReportRequest($params);
        $response = $this->getClient()->runReport($request);

        return $this->formatResponse($response);
    }

    public function fetchMostVisitedPages(Period $period, int $maxResults = 20): Collection
    {
        return $this->dateRange($period)
            ->metrics('screenPageViews')
            ->dimensions(['pageTitle', 'fullPageUrl'])
            ->orderByMetricDesc('screenPageViews')
            ->limit($maxResults)
            ->get()
            ->table;
    }

    public function fetchTopReferrers(Period $period, int $maxResults = 20): Collection
    {
        return $this->dateRange($period)
            ->metrics('screenPageViews')
            ->dimensions('sessionSource')
            ->orderByMetricDesc('screenPageViews')
            ->limit($maxResults)
            ->get()
            ->table;
    }

    public function fetchTopBrowsers(Period $period, int $maxResults = 10): Collection
    {
        return $this->dateRange($period)
            ->metrics('sessions')
            ->dimensions('browser')
            ->orderByMetricDesc('sessions')
            ->get()
            ->table;
    }

    public function performQuery(Period $period, string|array $metrics, string|array $dimensions = []): Collection
    {
        $that = clone $this;

        $query = $that
            ->dateRange($period)
            ->metrics($metrics);

        if ($dimensions) {
            $query = $query->dimensions($dimensions);
        }

        return $query->get()->table;
    }
}

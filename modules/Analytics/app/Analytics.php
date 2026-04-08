<?php

namespace Modules\Analytics;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\MetricAggregation;
use Google\Analytics\Data\V1beta\RunRealtimeReportRequest;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

    private ?BetaAnalyticsDataClient $client = null;

    private readonly string $credentialsFileName;

    public function __construct(int|string $propertyId, string $credentials)
    {
        $this->propertyId = $propertyId;
        $this->credentials = $credentials;
        $this->credentialsFileName = 'analytics-credentials.json';
    }

    public function getCredentials(): string
    {
        return $this->credentials;
    }

    public function getClient(): BetaAnalyticsDataClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $credentialsPath = $this->ensureCredentialsFile();

        return $this->client = new BetaAnalyticsDataClient([
            'credentials' => $credentialsPath,
        ]);
    }

    private function ensureCredentialsFile(): string
    {
        $credentials = $this->getCredentials();
        $currentHash = md5($credentials);
        $cachedHash = Cache::get('analytics.credentials_hash');

        $storage = Storage::disk('local');

        if ($cachedHash !== $currentHash || ! $storage->exists($this->credentialsFileName)) {
            $storage->put($this->credentialsFileName, $credentials);
            Cache::forever('analytics.credentials_hash', $currentHash);
        }

        $path = $storage->path($this->credentialsFileName);

        if (! file_exists($path)) {
            throw new InvalidConfiguration('The credentials file does not exist.');
        }

        return $path;
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

    public function fetchTopCountries(Period $period, int $maxResults = 20): Collection
    {
        $that = clone $this;

        return $that->dateRange($period)
            ->metrics('sessions')
            ->dimensions('country')
            ->orderByMetricDesc('sessions')
            ->limit($maxResults)
            ->get()
            ->table;
    }

    public function fetchDeviceCategories(Period $period): Collection
    {
        $that = clone $this;

        return $that->dateRange($period)
            ->metrics('sessions')
            ->dimensions('deviceCategory')
            ->orderByMetricDesc('sessions')
            ->get()
            ->table;
    }

    public function fetchOperatingSystems(Period $period, int $maxResults = 10): Collection
    {
        $that = clone $this;

        return $that->dateRange($period)
            ->metrics('sessions')
            ->dimensions('operatingSystem')
            ->orderByMetricDesc('sessions')
            ->limit($maxResults)
            ->get()
            ->table;
    }

    public function fetchTrafficSources(Period $period, int $maxResults = 10): Collection
    {
        $that = clone $this;

        return $that->dateRange($period)
            ->metrics('sessions')
            ->dimensions(['sessionSource', 'sessionMedium'])
            ->orderByMetricDesc('sessions')
            ->limit($maxResults)
            ->get()
            ->table;
    }

    public function fetchSessionMetrics(Period $period): array
    {
        $that = clone $this;

        $response = $that->dateRange($period)
            ->metrics(['sessions', 'newUsers', 'averageSessionDuration'])
            ->metricAggregation(MetricAggregation::TOTAL)
            ->get();

        $totals = collect($response->metricAggregationsTable)->first() ?? [];

        return [
            'sessions' => (int) ($totals['sessions'] ?? 0),
            'new_users' => (int) ($totals['newUsers'] ?? 0),
            'avg_session_duration' => (float) ($totals['averageSessionDuration'] ?? 0),
        ];
    }

    public function fetchLandingPages(Period $period, int $maxResults = 10): Collection
    {
        $that = clone $this;

        return $that->dateRange($period)
            ->metrics(['sessions', 'bounceRate'])
            ->dimensions('landingPage')
            ->orderByMetricDesc('sessions')
            ->limit($maxResults)
            ->get()
            ->table;
    }

    public function fetchExitPages(Period $period, int $maxResults = 10): Collection
    {
        $that = clone $this;

        return $that->dateRange($period)
            ->metrics(['sessions', 'screenPageViews'])
            ->dimensions('pagePath')
            ->orderByMetricDesc('sessions')
            ->limit($maxResults)
            ->get()
            ->table;
    }

    public function fetchChannelTrend(Period $period): Collection
    {
        $that = clone $this;

        return $that->dateRange($period)
            ->metrics('sessions')
            ->dimensions(['date', 'sessionMedium'])
            ->get()
            ->table;
    }

    public function fetchHourlyHeatmap(Period $period): Collection
    {
        $that = clone $this;

        return $that->dateRange($period)
            ->metrics('sessions')
            ->dimensions(['hour', 'dayOfWeek'])
            ->get()
            ->table;
    }

    public function fetchPreviousPeriodOverview(Period $period): array
    {
        $that = clone $this;

        $days = $period->startDate->diffInDays($period->endDate) + 1;
        $prevEnd = $period->startDate->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);
        $previousPeriod = Period::create($prevStart, $prevEnd);

        $response = $that->dateRange($previousPeriod)
            ->metrics(['sessions', 'totalUsers', 'screenPageViews', 'bounceRate'])
            ->metricAggregation(MetricAggregation::TOTAL)
            ->get();

        $totals = collect($response->metricAggregationsTable)->first() ?? [];

        return [
            'sessions' => (int) ($totals['sessions'] ?? 0),
            'totalUsers' => (int) ($totals['totalUsers'] ?? 0),
            'screenPageViews' => (int) ($totals['screenPageViews'] ?? 0),
            'bounceRate' => (float) ($totals['bounceRate'] ?? 0),
        ];
    }

    public function fetchRealtimeUsers(): int
    {
        $request = new RunRealtimeReportRequest([
            'property' => 'properties/'.$this->getPropertyId(),
            'metrics' => [new Metric(['name' => 'activeUsers'])],
        ]);

        $response = $this->getClient()->runRealtimeReport($request);

        foreach ($response->getRows() as $row) {
            foreach ($row->getMetricValues() as $value) {
                return (int) $value->getValue();
            }
        }

        return 0;
    }

    public function fetchSearchTerms(Period $period, int $maxResults = 20): Collection
    {
        $that = clone $this;

        return $that->dateRange($period)
            ->metrics(['sessions', 'screenPageViews'])
            ->dimensions('searchTerm')
            ->orderByMetricDesc('sessions')
            ->limit($maxResults)
            ->get()
            ->table;
    }

    public function fetchUserFlow(Period $period, int $maxResults = 10): Collection
    {
        $that = clone $this;

        return $that->dateRange($period)
            ->metrics(['sessions', 'screenPageViews', 'bounceRate'])
            ->dimensions(['landingPage', 'pagePath'])
            ->orderByMetricDesc('sessions')
            ->limit($maxResults)
            ->get()
            ->table;
    }
}

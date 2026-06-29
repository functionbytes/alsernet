<?php

namespace Modules\Analytics\Abstracts;

use Illuminate\Support\Collection;
use Modules\Analytics\Period;

interface AnalyticsContract
{
    public function fetchMostVisitedPages(Period $period, int $maxResults = 20): Collection;

    public function fetchTopReferrers(Period $period, int $maxResults = 20): Collection;

    public function fetchTopBrowsers(Period $period, int $maxResults = 10): Collection;

    public function performQuery(Period $period, string|array $metrics, string|array $dimensions = []): Collection;

    public function fetchTopCountries(Period $period, int $maxResults = 20): Collection;

    public function fetchDeviceCategories(Period $period): Collection;

    public function fetchOperatingSystems(Period $period, int $maxResults = 10): Collection;

    public function fetchTrafficSources(Period $period, int $maxResults = 10): Collection;

    public function fetchSessionMetrics(Period $period): array;

    public function fetchLandingPages(Period $period, int $maxResults = 10): Collection;

    public function fetchExitPages(Period $period, int $maxResults = 10): Collection;

    public function fetchChannelTrend(Period $period): Collection;

    public function fetchHourlyHeatmap(Period $period): Collection;

    public function fetchPreviousPeriodOverview(Period $period): array;

    public function fetchRealtimeUsers(): int;

    public function fetchSearchTerms(Period $period, int $maxResults = 20): Collection;

    public function fetchUserFlow(Period $period, int $maxResults = 10): Collection;
}

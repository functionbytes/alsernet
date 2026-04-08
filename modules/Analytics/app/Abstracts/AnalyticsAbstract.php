<?php

namespace Modules\Analytics\Abstracts;

use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Modules\Analytics\Period;

abstract class AnalyticsAbstract
{
    use Macroable;

    public ?string $propertyId = null;

    public ?string $credentials = null;

    public function getPropertyId(): string
    {
        return $this->propertyId;
    }

    abstract public function fetchMostVisitedPages(Period $period, int $maxResults = 20): Collection;

    abstract public function fetchTopReferrers(Period $period, int $maxResults = 20): Collection;

    abstract public function fetchTopBrowsers(Period $period, int $maxResults = 10): Collection;

    abstract public function fetchTopCountries(Period $period, int $maxResults = 20): Collection;

    abstract public function fetchDeviceCategories(Period $period): Collection;

    abstract public function fetchOperatingSystems(Period $period, int $maxResults = 10): Collection;

    abstract public function fetchTrafficSources(Period $period, int $maxResults = 10): Collection;

    abstract public function fetchSessionMetrics(Period $period): array;

    abstract public function fetchLandingPages(Period $period, int $maxResults = 10): Collection;

    abstract public function fetchExitPages(Period $period, int $maxResults = 10): Collection;

    abstract public function fetchChannelTrend(Period $period): Collection;

    abstract public function fetchHourlyHeatmap(Period $period): Collection;

    abstract public function fetchPreviousPeriodOverview(Period $period): array;

    abstract public function fetchRealtimeUsers(): int;

    abstract public function fetchSearchTerms(Period $period, int $maxResults = 20): Collection;

    abstract public function fetchUserFlow(Period $period, int $maxResults = 10): Collection;
}

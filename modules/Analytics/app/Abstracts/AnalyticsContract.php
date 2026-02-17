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
}

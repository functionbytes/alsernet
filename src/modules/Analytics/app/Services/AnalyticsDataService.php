<?php

namespace Modules\Analytics\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Analytics\Analytics;
use Modules\Analytics\Period;

class AnalyticsDataService
{
    public function __construct(
        private readonly Analytics $analytics
    ) {}

    /**
     * Build an Analytics instance from stored settings.
     *
     * @throws \Exception when credentials are missing or cannot be decrypted.
     */
    public static function fromSettings(): self
    {
        $propertyId = setting('google_analytics_property_id');
        $stored = setting('google_analytics_credentials');

        if (! $propertyId || ! $stored) {
            throw new \Exception('Analytics no está configurado. Por favor, configure la Property ID y las credenciales.');
        }

        try {
            $credentials = decrypt($stored);
        } catch (DecryptException) {
            throw new \Exception('Analytics no está configurado. Por favor, configure la Property ID y las credenciales.');
        }

        return new self(new Analytics($propertyId, $credentials));
    }

    /**
     * Overview: sessions, users, pageviews, bounce rate with chart data by date.
     *
     * @return array{chart_data: array<int, mixed>, totals: array{sessions: int, totalUsers: int, screenPageViews: int, bounceRate: float}}
     */
    public function fetchOverview(Period $period, string $range): mixed
    {
        return $this->cachedData('overview', $range, function () use ($period) {
            $response = $this->analytics
                ->dateRange($period)
                ->metrics(['sessions', 'totalUsers', 'screenPageViews', 'bounceRate'])
                ->dimensions('date')
                ->get();

            $chartData = $response->table->toArray();
            usort($chartData, fn ($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));

            $sessions = (int) array_sum(array_column($chartData, 'sessions'));
            $totalUsers = (int) array_sum(array_column($chartData, 'totalUsers'));
            $pageViews = (int) array_sum(array_column($chartData, 'screenPageViews'));
            $bounceRate = $sessions > 0
                ? array_sum(array_map(
                    fn ($r) => (float) ($r['bounceRate'] ?? 0) * (int) ($r['sessions'] ?? 0),
                    $chartData
                )) / $sessions
                : 0.0;

            return [
                'chart_data' => $chartData,
                'totals' => [
                    'sessions' => $sessions,
                    'totalUsers' => $totalUsers,
                    'screenPageViews' => $pageViews,
                    'bounceRate' => round($bounceRate, 4),
                ],
            ];
        });
    }

    /**
     * Top visited pages: title, URL, and view count.
     *
     * @return array<int, array{title: string, url: string, views: int}>
     */
    public function fetchTopPages(Period $period, string $range): mixed
    {
        return $this->cachedData('top_pages', $range, function () use ($period) {
            $pages = $this->analytics->fetchMostVisitedPages($period, 50);

            return $pages->map(fn ($page) => [
                'title' => $page['pageTitle'] ?? 'Unknown',
                'url' => $page['fullPageUrl'] ?? '#',
                'views' => (int) ($page['screenPageViews'] ?? 0),
            ])->values()->toArray();
        });
    }

    /**
     * Top browsers by session count.
     *
     * @return array<int, array{name: string, sessions: int}>
     */
    public function fetchTopBrowsers(Period $period, string $range): mixed
    {
        return $this->cachedData('top_browsers', $range, function () use ($period) {
            $browsers = $this->analytics->fetchTopBrowsers($period, 10);

            return $browsers->map(fn ($browser) => [
                'name' => $browser['browser'] ?? 'Unknown',
                'sessions' => (int) ($browser['sessions'] ?? 0),
            ])->values()->toArray();
        });
    }

    /**
     * Top referrers by pageview count.
     *
     * @return array<int, array{source: string, views: int}>
     */
    public function fetchTopReferrers(Period $period, string $range): mixed
    {
        return $this->cachedData('top_referrers', $range, function () use ($period) {
            $referrers = $this->analytics->fetchTopReferrers($period, 50);

            return $referrers->map(fn ($referrer) => [
                'source' => $referrer['sessionSource'] ?? 'Direct',
                'views' => (int) ($referrer['screenPageViews'] ?? 0),
            ])->values()->toArray();
        });
    }

    /**
     * Top countries by session count with percentage share.
     *
     * @return array<int, array{country: string, sessions: int, percentage: float}>
     */
    public function fetchTopCountries(Period $period, string $range): mixed
    {
        return $this->cachedData('top_countries', $range, function () use ($period) {
            $countries = $this->analytics->fetchTopCountries($period, 20);
            $total = $countries->sum(fn ($row) => (int) ($row['sessions'] ?? 0));

            return $countries->map(fn ($row) => [
                'country' => $row['country'] ?? 'Unknown',
                'sessions' => (int) ($row['sessions'] ?? 0),
                'percentage' => $total > 0 ? round(((int) ($row['sessions'] ?? 0) / $total) * 100, 1) : 0,
            ])->values()->toArray();
        });
    }

    /**
     * Device category breakdown with percentage share.
     *
     * @return array<int, array{device: string, sessions: int, percentage: float}>
     */
    public function fetchDeviceCategories(Period $period, string $range): mixed
    {
        return $this->cachedData('device_categories', $range, function () use ($period) {
            $devices = $this->analytics->fetchDeviceCategories($period);
            $total = $devices->sum(fn ($row) => (int) ($row['sessions'] ?? 0));

            return $devices->map(fn ($row) => [
                'device' => $row['deviceCategory'] ?? 'Unknown',
                'sessions' => (int) ($row['sessions'] ?? 0),
                'percentage' => $total > 0 ? round(((int) ($row['sessions'] ?? 0) / $total) * 100, 1) : 0,
            ])->values()->toArray();
        });
    }

    /**
     * Operating system breakdown with percentage share.
     *
     * @return array<int, array{os: string, sessions: int, percentage: float}>
     */
    public function fetchOperatingSystems(Period $period, string $range): mixed
    {
        return $this->cachedData('operating_systems', $range, function () use ($period) {
            $osList = $this->analytics->fetchOperatingSystems($period, 10);
            $total = $osList->sum(fn ($row) => (int) ($row['sessions'] ?? 0));

            return $osList->map(fn ($row) => [
                'os' => $row['operatingSystem'] ?? 'Unknown',
                'sessions' => (int) ($row['sessions'] ?? 0),
                'percentage' => $total > 0 ? round(((int) ($row['sessions'] ?? 0) / $total) * 100, 1) : 0,
            ])->values()->toArray();
        });
    }

    /**
     * Traffic sources (source + medium) with percentage share.
     *
     * @return array<int, array{source: string, medium: string, sessions: int, percentage: float}>
     */
    public function fetchTrafficSources(Period $period, string $range): mixed
    {
        return $this->cachedData('traffic_sources', $range, function () use ($period) {
            $sources = $this->analytics->fetchTrafficSources($period, 50);
            $total = $sources->sum(fn ($row) => (int) ($row['sessions'] ?? 0));

            return $sources->map(fn ($row) => [
                'source' => $row['sessionSource'] ?? 'Unknown',
                'medium' => $row['sessionMedium'] ?? 'none',
                'sessions' => (int) ($row['sessions'] ?? 0),
                'percentage' => $total > 0 ? round(((int) ($row['sessions'] ?? 0) / $total) * 100, 1) : 0,
            ])->values()->toArray();
        });
    }

    /**
     * Session metrics totals: sessions, new users, average duration.
     *
     * @return array{sessions: int, new_users: int, avg_session_duration: float}
     */
    public function fetchSessionMetrics(Period $period, string $range): mixed
    {
        return $this->cachedData('session_metrics', $range, function () use ($period) {
            return $this->analytics->fetchSessionMetrics($period);
        });
    }

    /**
     * Realtime active users — always fresh, no cache.
     */
    public function fetchRealtimeUsers(): int
    {
        return $this->analytics->fetchRealtimeUsers();
    }

    /**
     * Landing pages with session count and bounce rate.
     *
     * @return array<int, array{page: string, sessions: int, bounce_rate: float}>
     */
    public function fetchLandingPages(Period $period, string $range): mixed
    {
        return $this->cachedData('landing_pages', $range, function () use ($period) {
            $pages = $this->analytics->fetchLandingPages($period, 50);

            return $pages->map(fn ($p) => [
                'page' => $p['landingPage'] ?? '/',
                'sessions' => (int) ($p['sessions'] ?? 0),
                'bounce_rate' => round((float) ($p['bounceRate'] ?? 0) * 100, 1),
            ])->values()->toArray();
        });
    }

    /**
     * Exit pages with session and pageview counts.
     *
     * @return array<int, array{page: string, sessions: int, pageviews: int}>
     */
    public function fetchExitPages(Period $period, string $range): mixed
    {
        return $this->cachedData('exit_pages', $range, function () use ($period) {
            $pages = $this->analytics->fetchExitPages($period, 50);

            return $pages->map(fn ($p) => [
                'page' => $p['pagePath'] ?? '/',
                'sessions' => (int) ($p['sessions'] ?? 0),
                'pageviews' => (int) ($p['screenPageViews'] ?? 0),
            ])->values()->toArray();
        });
    }

    /**
     * Channel trend grouped by date and session medium.
     *
     * @return array{dates: array<int, string>, mediums: array<int, string>, series: array<int, array{name: string, data: array<int, int>}>}
     */
    public function fetchChannelTrend(Period $period, string $range): mixed
    {
        return $this->cachedData('channel_trend', $range, function () use ($period) {
            $rows = $this->analytics->fetchChannelTrend($period);

            $grouped = [];
            $mediums = [];

            foreach ($rows as $row) {
                $date = $row['date'] ?? '';
                $medium = $row['sessionMedium'] ?? 'none';
                $sessions = (int) ($row['sessions'] ?? 0);

                if (! isset($grouped[$date])) {
                    $grouped[$date] = [];
                }
                $grouped[$date][$medium] = $sessions;

                if (! in_array($medium, $mediums)) {
                    $mediums[] = $medium;
                }
            }

            return [
                'dates' => array_keys($grouped),
                'mediums' => $mediums,
                'series' => collect($mediums)->map(fn ($medium) => [
                    'name' => $medium,
                    'data' => collect(array_keys($grouped))->map(fn ($date) => $grouped[$date][$medium] ?? 0)->toArray(),
                ])->toArray(),
            ];
        });
    }

    /**
     * Hourly heatmap: 7×24 matrix of sessions by day-of-week and hour.
     *
     * @return array{matrix: array<int, array<int, int>>, days: array<int, string>}
     */
    public function fetchHourlyHeatmap(Period $period, string $range): mixed
    {
        return $this->cachedData('hourly_heatmap', $range, function () use ($period) {
            $rows = $this->analytics->fetchHourlyHeatmap($period);

            // Build 7x24 matrix (day 0-6, hour 0-23); GA4: dayOfWeek 0=Sunday
            $matrix = array_fill(0, 7, array_fill(0, 24, 0));

            foreach ($rows as $row) {
                $hour = (int) ($row['hour'] ?? 0);
                $day = (int) ($row['dayOfWeek'] ?? 0);
                $sessions = (int) ($row['sessions'] ?? 0);

                if (isset($matrix[$day][$hour])) {
                    $matrix[$day][$hour] += $sessions;
                }
            }

            return [
                'matrix' => $matrix,
                'days' => ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
            ];
        });
    }

    /**
     * Previous-period comparison for the main overview metrics.
     *
     * @return array{sessions: int, totalUsers: int, screenPageViews: int, bounceRate: float}
     */
    public function fetchComparison(Period $period, string $range): mixed
    {
        return $this->cachedData('comparison', $range, function () use ($period) {
            return $this->analytics->fetchPreviousPeriodOverview($period);
        });
    }

    /**
     * Internal search terms with session and pageview counts.
     *
     * @return array<int, array{term: string, sessions: int, pageviews: int, percentage: float}>
     */
    public function fetchSearchTerms(Period $period, string $range): mixed
    {
        return $this->cachedData('search_terms', $range, function () use ($period) {
            $terms = $this->analytics->fetchSearchTerms($period, 50);
            $total = $terms->sum(fn ($r) => (int) ($r['sessions'] ?? 0));

            return $terms->map(fn ($r) => [
                'term' => $r['searchTerm'] ?? '(no term)',
                'sessions' => (int) ($r['sessions'] ?? 0),
                'pageviews' => (int) ($r['screenPageViews'] ?? 0),
                'percentage' => $total > 0 ? round(((int) ($r['sessions'] ?? 0) / $total) * 100, 1) : 0,
            ])->values()->toArray();
        });
    }

    /**
     * User flow: landing → exit page pairs with engagement metrics.
     *
     * @return array<int, array{landing: string, exit: string, sessions: int, pageviews: int, bounce_rate: float}>
     */
    public function fetchUserFlow(Period $period, string $range): mixed
    {
        return $this->cachedData('user_flow', $range, function () use ($period) {
            $rows = $this->analytics->fetchUserFlow($period, 50);

            return $rows->map(fn ($r) => [
                'landing' => $r['landingPage'] ?? '/',
                'exit' => $r['pagePath'] ?? '/',
                'sessions' => (int) ($r['sessions'] ?? 0),
                'pageviews' => (int) ($r['screenPageViews'] ?? 0),
                'bounce_rate' => round((float) ($r['bounceRate'] ?? 0) * 100, 1),
            ])->values()->toArray();
        });
    }

    /**
     * Run a custom metric/dimension query and return the raw table collection.
     *
     * @param  string|array<int, string>  $metrics
     * @param  string|array<int, string>  $dimensions
     */
    public function performQuery(Period $period, string|array $metrics, string|array $dimensions = []): Collection
    {
        return $this->analytics->performQuery($period, $metrics, $dimensions);
    }

    /**
     * Dashboard widget data: daily pageviews, top 5 pages, device breakdown.
     *
     * @return array{daily_views: array<int, mixed>, top_pages: array<int, mixed>, devices: array<int, mixed>}
     */
    public function fetchDashboardWidget(Period $period): array
    {
        $dailyViews = $this->cachedData('widget_overview', 'last_7_days', function () use ($period) {
            $response = $this->analytics
                ->dateRange($period)
                ->metrics(['screenPageViews'])
                ->dimensions('date')
                ->get();

            return collect($response->table)->map(fn ($row) => [
                'date' => $row['date'] ?? '',
                'views' => (int) ($row['screenPageViews'] ?? 0),
            ])->values()->toArray();
        });

        $topPages = $this->cachedData('widget_top_pages', 'last_7_days', function () use ($period) {
            $pages = $this->analytics->fetchMostVisitedPages($period, 5);

            return $pages->map(fn ($page) => [
                'title' => $page['pageTitle'] ?? 'Unknown',
                'views' => (int) ($page['screenPageViews'] ?? 0),
            ])->values()->toArray();
        });

        $devices = $this->cachedData('widget_devices', 'last_7_days', function () use ($period) {
            $rows = $this->analytics->fetchDeviceCategories($period);
            $total = $rows->sum(fn ($r) => (int) ($r['sessions'] ?? 0));

            return $rows->map(fn ($r) => [
                'device' => $r['deviceCategory'] ?? 'Unknown',
                'sessions' => (int) ($r['sessions'] ?? 0),
                'percentage' => $total > 0 ? round(((int) ($r['sessions'] ?? 0) / $total) * 100, 1) : 0,
            ])->values()->toArray();
        });

        return [
            'daily_views' => $dailyViews,
            'top_pages' => $topPages,
            'devices' => $devices,
        ];
    }

    /**
     * Cache helper — wraps callable with tagged cache using a range-based key.
     * TTL is driven by config('analytics.cache_lifetime', 60) minutes.
     */
    private function cachedData(string $key, string $range, callable $callback): mixed
    {
        $cacheKey = "analytics_{$key}_{$range}";
        $ttl = (int) config('analytics.cache_lifetime', 60) * 60;

        $store = Cache::supportsTags()
            ? Cache::tags(['analytics'])
            : Cache::store();

        return $store->remember($cacheKey, $ttl, $callback);
    }
}

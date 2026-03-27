<?php

namespace Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Analytics\Analytics;
use Modules\Analytics\Period;

class AnalyticsController extends Controller
{
    /**
     * Get analytics overview
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('overview', $range, function () use ($period) {
                $response = $this->getAnalyticsInstance()
                    ->dateRange($period)
                    ->metrics(['sessions', 'totalUsers', 'screenPageViews', 'bounceRate'])
                    ->dimensions('date')
                    ->get();

                $totals = collect($response->metricAggregationsTable)
                    ->firstWhere('aggregation', 'TOTAL') ?? [];

                return [
                    'chart_data' => $response->table->toArray(),
                    'totals' => [
                        'sessions' => (int) ($totals['sessions'] ?? 0),
                        'totalUsers' => (int) ($totals['totalUsers'] ?? 0),
                        'screenPageViews' => (int) ($totals['screenPageViews'] ?? 0),
                        'bounceRate' => (float) ($totals['bounceRate'] ?? 0),
                    ],
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'overview');
        }
    }

    /**
     * Get top visited pages
     */
    public function topPages(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('top_pages', $range, function () use ($period) {
                $pages = $this->getAnalyticsInstance()->fetchMostVisitedPages($period, 10);

                return $pages->map(fn ($page) => [
                    'title' => $page['pageTitle'] ?? 'Unknown',
                    'url' => $page['fullPageUrl'] ?? '#',
                    'views' => (int) ($page['screenPageViews'] ?? 0),
                ])->values()->toArray();
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'topPages');
        }
    }

    /**
     * Get top browsers
     */
    public function topBrowsers(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('top_browsers', $range, function () use ($period) {
                $browsers = $this->getAnalyticsInstance()->fetchTopBrowsers($period, 10);

                return $browsers->map(fn ($browser) => [
                    'name' => $browser['browser'] ?? 'Unknown',
                    'sessions' => (int) ($browser['sessions'] ?? 0),
                ])->values()->toArray();
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'topBrowsers');
        }
    }

    /**
     * Get top referrers
     */
    public function topReferrers(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('top_referrers', $range, function () use ($period) {
                $referrers = $this->getAnalyticsInstance()->fetchTopReferrers($period, 10);

                return $referrers->map(fn ($referrer) => [
                    'source' => $referrer['sessionSource'] ?? 'Direct',
                    'views' => (int) ($referrer['screenPageViews'] ?? 0),
                ])->values()->toArray();
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'topReferrers');
        }
    }

    /**
     * Get custom query
     */
    public function query(Request $request): JsonResponse
    {
        try {
            $period = $this->getPeriodFromRequest($request);

            $metrics = $request->input('metrics', ['sessions', 'screenPageViews']);
            $dimensions = $request->input('dimensions', 'date');

            $analytics = $this->getAnalyticsInstance();

            $data = $analytics->performQuery($period, $metrics, $dimensions);

            return response()->json([
                'success' => true,
                'data' => $data->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'query');
        }
    }

    /**
     * Get top countries by sessions
     */
    public function topCountries(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('top_countries', $range, function () use ($period) {
                $countries = $this->getAnalyticsInstance()->fetchTopCountries($period, 20);
                $total = $countries->sum(fn ($row) => (int) ($row['sessions'] ?? 0));

                return $countries->map(fn ($row) => [
                    'country' => $row['country'] ?? 'Unknown',
                    'sessions' => (int) ($row['sessions'] ?? 0),
                    'percentage' => $total > 0 ? round(((int) ($row['sessions'] ?? 0) / $total) * 100, 1) : 0,
                ])->values()->toArray();
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'topCountries');
        }
    }

    /**
     * Get device category breakdown
     */
    public function deviceCategories(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('device_categories', $range, function () use ($period) {
                $devices = $this->getAnalyticsInstance()->fetchDeviceCategories($period);
                $total = $devices->sum(fn ($row) => (int) ($row['sessions'] ?? 0));

                return $devices->map(fn ($row) => [
                    'device' => $row['deviceCategory'] ?? 'Unknown',
                    'sessions' => (int) ($row['sessions'] ?? 0),
                    'percentage' => $total > 0 ? round(((int) ($row['sessions'] ?? 0) / $total) * 100, 1) : 0,
                ])->values()->toArray();
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'deviceCategories');
        }
    }

    /**
     * Get operating system breakdown
     */
    public function operatingSystems(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('operating_systems', $range, function () use ($period) {
                $osList = $this->getAnalyticsInstance()->fetchOperatingSystems($period, 10);
                $total = $osList->sum(fn ($row) => (int) ($row['sessions'] ?? 0));

                return $osList->map(fn ($row) => [
                    'os' => $row['operatingSystem'] ?? 'Unknown',
                    'sessions' => (int) ($row['sessions'] ?? 0),
                    'percentage' => $total > 0 ? round(((int) ($row['sessions'] ?? 0) / $total) * 100, 1) : 0,
                ])->values()->toArray();
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'operatingSystems');
        }
    }

    /**
     * Get traffic sources (source + medium)
     */
    public function trafficSources(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('traffic_sources', $range, function () use ($period) {
                $sources = $this->getAnalyticsInstance()->fetchTrafficSources($period, 10);
                $total = $sources->sum(fn ($row) => (int) ($row['sessions'] ?? 0));

                return $sources->map(fn ($row) => [
                    'source' => $row['sessionSource'] ?? 'Unknown',
                    'medium' => $row['sessionMedium'] ?? 'none',
                    'sessions' => (int) ($row['sessions'] ?? 0),
                    'percentage' => $total > 0 ? round(((int) ($row['sessions'] ?? 0) / $total) * 100, 1) : 0,
                ])->values()->toArray();
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'trafficSources');
        }
    }

    /**
     * Get session metrics (avg duration, new users, sessions)
     */
    public function sessionMetrics(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('session_metrics', $range, function () use ($period) {
                return $this->getAnalyticsInstance()->fetchSessionMetrics($period);
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'sessionMetrics');
        }
    }

    /**
     * Get realtime active users (no cache, always fresh)
     */
    public function realtimeUsers(Request $request): JsonResponse
    {
        try {
            $users = $this->getAnalyticsInstance()->fetchRealtimeUsers();

            return response()->json(['success' => true, 'data' => ['active_users' => $users]]);
        } catch (\Exception $e) {
            return response()->json(['success' => true, 'data' => ['active_users' => 0]]);
        }
    }

    /**
     * Get landing pages
     */
    public function landingPages(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('landing_pages', $range, function () use ($period) {
                $pages = $this->getAnalyticsInstance()->fetchLandingPages($period, 10);

                return $pages->map(fn ($p) => [
                    'page' => $p['landingPage'] ?? '/',
                    'sessions' => (int) ($p['sessions'] ?? 0),
                    'bounce_rate' => round((float) ($p['bounceRate'] ?? 0) * 100, 1),
                ])->values()->toArray();
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'landingPages');
        }
    }

    /**
     * Get exit pages
     */
    public function exitPages(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('exit_pages', $range, function () use ($period) {
                $pages = $this->getAnalyticsInstance()->fetchExitPages($period, 10);

                return $pages->map(fn ($p) => [
                    'page' => $p['exitPage'] ?? '/',
                    'sessions' => (int) ($p['sessions'] ?? 0),
                    'pageviews' => (int) ($p['screenPageViews'] ?? 0),
                ])->values()->toArray();
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'exitPages');
        }
    }

    /**
     * Get channel trend over time
     */
    public function channelTrend(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('channel_trend', $range, function () use ($period) {
                $rows = $this->getAnalyticsInstance()->fetchChannelTrend($period);

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

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'channelTrend');
        }
    }

    /**
     * Get hourly heatmap data
     */
    public function hourlyHeatmap(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('hourly_heatmap', $range, function () use ($period) {
                $rows = $this->getAnalyticsInstance()->fetchHourlyHeatmap($period);

                // Build 7x24 matrix (day 0-6, hour 0-23)
                $matrix = array_fill(0, 7, array_fill(0, 24, 0));

                foreach ($rows as $row) {
                    $hour = (int) ($row['hour'] ?? 0);
                    $day = (int) ($row['dayOfWeek'] ?? 0); // GA4: 0=Sunday
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

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'hourlyHeatmap');
        }
    }

    /**
     * Get comparison with previous period
     */
    public function comparison(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('comparison', $range, function () use ($period) {
                return $this->getAnalyticsInstance()->fetchPreviousPeriodOverview($period);
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'comparison');
        }
    }

    /**
     * Cache helper — wraps callable with tagged cache using range-based key.
     */
    protected function cachedData(string $key, string $range, callable $callback): mixed
    {
        $cacheKey = "analytics_{$key}_{$range}";
        $minutes = (int) setting('analytics_cache_lifetime', 60);

        return Cache::tags(['analytics'])->remember($cacheKey, $minutes * 60, $callback);
    }

    /**
     * Get period from request
     */
    protected function getPeriodFromRequest(Request $request): Period
    {
        $range = $request->input('range', 'last_7_days');

        return match ($range) {
            'today' => Period::days(1),
            'yesterday' => Period::days(1),
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'this_month' => Period::thisMonth(),
            'last_month' => Period::lastMonth(),
            'this_year' => Period::thisYear(),
            default => Period::last7Days(),
        };
    }

    /**
     * Get Analytics instance
     */
    protected function getAnalyticsInstance(): Analytics
    {
        $propertyId = setting('google_analytics_property_id');
        $credentials = setting('google_analytics_credentials');

        if (! $propertyId || ! $credentials) {
            throw new \Exception('Analytics no está configurado. Por favor, configure la Property ID y las credenciales.');
        }

        return new Analytics($propertyId, $credentials);
    }

    /**
     * Get internal search terms
     */
    public function searchTerms(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('search_terms', $range, function () use ($period) {
                $terms = $this->getAnalyticsInstance()->fetchSearchTerms($period, 20);
                $total = $terms->sum(fn ($r) => (int) ($r['sessions'] ?? 0));

                return $terms->map(fn ($r) => [
                    'term' => $r['searchTerm'] ?? '(no term)',
                    'sessions' => (int) ($r['sessions'] ?? 0),
                    'pageviews' => (int) ($r['screenPageViews'] ?? 0),
                    'percentage' => $total > 0 ? round(((int) ($r['sessions'] ?? 0) / $total) * 100, 1) : 0,
                ])->values()->toArray();
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'searchTerms');
        }
    }

    /**
     * Get user flow (landing → exit pages)
     */
    public function userFlow(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRequest($request);

            $data = $this->cachedData('user_flow', $range, function () use ($period) {
                $rows = $this->getAnalyticsInstance()->fetchUserFlow($period, 10);

                return $rows->map(fn ($r) => [
                    'landing' => $r['landingPage'] ?? '/',
                    'exit' => $r['exitPage'] ?? '/',
                    'sessions' => (int) ($r['sessions'] ?? 0),
                    'pageviews' => (int) ($r['screenPageViews'] ?? 0),
                    'bounce_rate' => round((float) ($r['bounceRate'] ?? 0) * 100, 1),
                ])->values()->toArray();
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'userFlow');
        }
    }

    /**
     * Compact widget data for the Core dashboard include.
     * Returns last 7 days page views by day, top 5 pages, and device breakdown.
     * Gracefully returns empty arrays when Analytics is not configured.
     */
    public function dashboardWidget(): JsonResponse
    {
        try {
            $period = Period::last7Days();

            $overview = $this->cachedData('widget_overview', 'last_7_days', function () use ($period) {
                $response = $this->getAnalyticsInstance()
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
                $pages = $this->getAnalyticsInstance()->fetchMostVisitedPages($period, 5);

                return $pages->map(fn ($page) => [
                    'title' => $page['pageTitle'] ?? 'Unknown',
                    'views' => (int) ($page['screenPageViews'] ?? 0),
                ])->values()->toArray();
            });

            $devices = $this->cachedData('widget_devices', 'last_7_days', function () use ($period) {
                $rows = $this->getAnalyticsInstance()->fetchDeviceCategories($period);
                $total = $rows->sum(fn ($r) => (int) ($r['sessions'] ?? 0));

                return $rows->map(fn ($r) => [
                    'device' => $r['deviceCategory'] ?? 'Unknown',
                    'sessions' => (int) ($r['sessions'] ?? 0),
                    'percentage' => $total > 0 ? round(((int) ($r['sessions'] ?? 0) / $total) * 100, 1) : 0,
                ])->values()->toArray();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'daily_views' => $overview,
                    'top_pages' => $topPages,
                    'devices' => $devices,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => ['daily_views' => [], 'top_pages' => [], 'devices' => []],
            ]);
        }
    }

    /**
     * Trigger report generation manually
     */
    public function triggerReport(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'daily');

            if (! in_array($type, ['daily', 'weekly', 'monthly'])) {
                return $this->errorResponse('Tipo de reporte inválido. Use: daily, weekly, monthly');
            }

            $email = $request->input('email', setting('analytics_reports_email', ''));

            \Modules\Analytics\Jobs\GenerateAnalyticsReport::dispatch($type, $email ?: null);

            return response()->json([
                'success' => true,
                'message' => "Reporte {$type} encolado correctamente.",
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'triggerReport');
        }
    }

    /**
     * Log exception and return a safe generic error response.
     */
    protected function errorResponse(\Exception $e, string $context, int $code = 400): JsonResponse
    {
        Log::error("Analytics error: {$context}", [
            'code' => $e->getCode(),
            'file' => class_basename($e->getFile()),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ha ocurrido un error al obtener los datos de Analytics. Por favor intenta más tarde.',
        ], $code);
    }
}

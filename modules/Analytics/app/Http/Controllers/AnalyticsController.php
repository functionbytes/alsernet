<?php

namespace Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Analytics\Jobs\GenerateAnalyticsReport;
use Modules\Analytics\Period;
use Modules\Analytics\Services\AnalyticsDataService;

class AnalyticsController extends Controller
{
    /**
     * Get analytics overview
     */
    public function overview(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchOverview($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'overview');
        }
    }

    /**
     * Get top visited pages
     */
    public function topPages(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchTopPages($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'topPages');
        }
    }

    /**
     * Get top browsers
     */
    public function topBrowsers(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchTopBrowsers($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'topBrowsers');
        }
    }

    /**
     * Get top referrers
     */
    public function topReferrers(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchTopReferrers($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'topReferrers');
        }
    }

    /**
     * Get custom query
     */
    public function query(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $metrics = $request->input('metrics', ['sessions', 'screenPageViews']);
            $dimensions = $request->input('dimensions', 'date');

            $data = AnalyticsDataService::fromSettings()->performQuery($this->getPeriodFromRange($range), $metrics, $dimensions);

            return response()->json(['success' => true, 'data' => $data->toArray()]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'query');
        }
    }

    /**
     * Get top countries by sessions
     */
    public function topCountries(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchTopCountries($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'topCountries');
        }
    }

    /**
     * Get device category breakdown
     */
    public function deviceCategories(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchDeviceCategories($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'deviceCategories');
        }
    }

    /**
     * Get operating system breakdown
     */
    public function operatingSystems(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchOperatingSystems($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'operatingSystems');
        }
    }

    /**
     * Get traffic sources (source + medium)
     */
    public function trafficSources(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchTrafficSources($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'trafficSources');
        }
    }

    /**
     * Get session metrics (avg duration, new users, sessions)
     */
    public function sessionMetrics(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchSessionMetrics($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'sessionMetrics');
        }
    }

    /**
     * Get realtime active users (no cache, always fresh)
     */
    public function realtimeUsers(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $users = AnalyticsDataService::fromSettings()->fetchRealtimeUsers();

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
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchLandingPages($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'landingPages');
        }
    }

    /**
     * Get exit pages
     */
    public function exitPages(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchExitPages($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'exitPages');
        }
    }

    /**
     * Get channel trend over time
     */
    public function channelTrend(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchChannelTrend($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'channelTrend');
        }
    }

    /**
     * Get hourly heatmap data
     */
    public function hourlyHeatmap(Request $request): JsonResponse
    {
        $this->authorize('analytics.data.view');

        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchHourlyHeatmap($this->getPeriodFromRange($range), $range)]);
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
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchComparison($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'comparison');
        }
    }

    /**
     * Get internal search terms
     */
    public function searchTerms(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', 'last_7_days');
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchSearchTerms($this->getPeriodFromRange($range), $range)]);
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
            $service = AnalyticsDataService::fromSettings();

            return response()->json(['success' => true, 'data' => $service->fetchUserFlow($this->getPeriodFromRange($range), $range)]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'userFlow');
        }
    }

    /**
     * Compact widget data for the Core dashboard include.
     * Gracefully returns empty arrays when Analytics is not configured.
     */
    public function dashboardWidget(): JsonResponse
    {
        try {
            $data = AnalyticsDataService::fromSettings()->fetchDashboardWidget(Period::last7Days());

            return response()->json(['success' => true, 'data' => $data]);
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
        $this->authorize('analytics.data.view');
        $this->authorize('analytics.reports.create');

        try {
            $type = $request->input('type', 'daily');

            if (! in_array($type, ['daily', 'weekly', 'monthly'])) {
                return $this->errorResponse(new \InvalidArgumentException(__('analytics::analytics.errors.invalid_report_type')), 'triggerReport');
            }

            $email = $request->input('email', setting('analytics_reports_email', ''));

            GenerateAnalyticsReport::dispatch($type, $email ?: null);

            return response()->json([
                'success' => true,
                'message' => __('analytics::analytics.success.report_queued_type', ['type' => $type]),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e, 'triggerReport');
        }
    }

    /**
     * Resolve a range string to a Period object.
     */
    private function getPeriodFromRange(string $range): Period
    {
        return match ($range) {
            'today', 'yesterday' => Period::days(1),
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'this_month' => Period::thisMonth(),
            'last_month' => Period::lastMonth(),
            'this_year' => Period::thisYear(),
            default => Period::last7Days(),
        };
    }

    /**
     * Log exception and return a safe generic error response.
     */
    private function errorResponse(\Exception $e, string $context, int $code = 400): JsonResponse
    {
        Log::error("Analytics error: {$context}", [
            'code' => $e->getCode(),
            'file' => class_basename($e->getFile()),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => __('analytics::analytics.errors.fetch_failed'),
        ], $code);
    }
}

<?php

namespace Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Analytics\Facades\Analytics;
use Modules\Analytics\Period;

class DashboardController extends Controller
{
    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        $pageTitle = 'Analytics Dashboard';
        $breadcrumb = 'Analytics / Dashboard';

        try {
            // Check if analytics is configured
            $propertyId = setting('google_analytics_property_id');
            $credentials = setting('google_analytics_credentials');

            if (empty($propertyId) || empty($credentials)) {
                return redirect()->route('settings.analytics.index')
                    ->with('error', 'Analytics no está configurado. Por favor, configura primero tu Google Analytics.');
            }

            // Obtener período seleccionado
            $range = $request->input('range', 'last_7_days');
            $period = $this->getPeriodFromRange($range);

            // Obtener datos de Analytics
            $overviewData = $this->getOverviewData($period);
            $topPages = $this->getTopPages($period);
            $topBrowsers = $this->getTopBrowsers($period);
            $topReferrers = $this->getTopReferrers($period);
            $dailyData = $this->getDailyData($period);

            $isConfigured = true;

            return view('analytics::dashboard.index', compact(
                'pageTitle',
                'breadcrumb',
                'range',
                'overviewData',
                'topPages',
                'topBrowsers',
                'topReferrers',
                'dailyData',
                'isConfigured'
            ));
        } catch (\RuntimeException $e) {
            // Handle credentials errors
            return redirect()->route('settings.analytics.index')
                ->with('error', 'Error en la configuración de Analytics: '.$e->getMessage());
        } catch (\Exception $e) {
            // Handle other errors gracefully
            \Log::error('Analytics dashboard error: '.$e->getMessage());

            return redirect()->route('settings.analytics.index')
                ->with('error', 'Error al cargar datos de Analytics. Por favor, verifica tu configuración.');
        }
    }

    /**
     * Get overview data (sessions, users, pageviews, bounce rate)
     */
    protected function getOverviewData(Period $period): array
    {
        try {
            $data = Analytics::dateRange($period)
                ->metrics(['sessions', 'totalUsers', 'screenPageViews', 'bounceRate'])
                ->get()
                ->getTotals();

            return [
                'sessions' => (int) ($data['metric_0'] ?? 0),
                'users' => (int) ($data['metric_1'] ?? 0),
                'pageviews' => (int) ($data['metric_2'] ?? 0),
                'bounce_rate' => (float) ($data['metric_3'] ?? 0),
            ];
        } catch (\Exception $e) {
            return ['sessions' => 0, 'users' => 0, 'pageviews' => 0, 'bounce_rate' => 0];
        }
    }

    /**
     * Get daily data for chart
     */
    protected function getDailyData(Period $period): array
    {
        try {
            $data = Analytics::dateRange($period)
                ->metrics(['sessions', 'screenPageViews'])
                ->dimensions('date')
                ->get()
                ->getTable();

            $dates = [];
            $sessions = [];
            $pageviews = [];

            foreach ($data as $row) {
                $dates[] = $row['0'] ?? date('Y-m-d');
                $sessions[] = (int) ($row['metric_0'] ?? 0);
                $pageviews[] = (int) ($row['metric_1'] ?? 0);
            }

            return [
                'dates' => $dates,
                'sessions' => $sessions,
                'pageviews' => $pageviews,
            ];
        } catch (\Exception $e) {
            return ['dates' => [], 'sessions' => [], 'pageviews' => []];
        }
    }

    /**
     * Get top pages
     */
    protected function getTopPages(Period $period): array
    {
        try {
            $data = Analytics::fetchMostVisitedPages($period, 10);

            return $data->map(function ($page) {
                return [
                    'title' => $page[0] ?? 'Unknown',
                    'url' => $page[1] ?? '#',
                    'views' => (int) ($page[2] ?? 0),
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get top browsers
     */
    protected function getTopBrowsers(Period $period): array
    {
        try {
            $data = Analytics::fetchTopBrowsers($period);

            return $data->map(function ($item) {
                return [
                    'name' => $item[0] ?? 'Unknown',
                    'sessions' => (int) ($item[1] ?? 0),
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get top referrers
     */
    protected function getTopReferrers(Period $period): array
    {
        try {
            $data = Analytics::fetchTopReferrers($period);

            return $data->map(function ($item) {
                return [
                    'source' => $item[0] ?? 'Direct',
                    'views' => (int) ($item[1] ?? 0),
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get period from range string
     */
    protected function getPeriodFromRange(string $range): Period
    {
        return match ($range) {
            'today' => Period::days(1),
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'this_month' => Period::thisMonth(),
            'last_month' => Period::lastMonth(),
            'this_year' => Period::thisYear(),
            default => Period::last7Days(),
        };
    }
}

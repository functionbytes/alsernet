<?php

namespace Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Analytics\Analytics;
use Modules\Analytics\Period;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    /**
     * Get analytics overview
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $period = $this->getPeriodFromRequest($request);

            $analytics = $this->getAnalyticsInstance();

            // Obtener estadísticas generales
            $response = $analytics
                ->dateRange($period)
                ->metrics(['sessions', 'totalUsers', 'screenPageViews', 'bounceRate'])
                ->dimensions('date')
                ->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'chart_data' => $response->getTable(),
                    'totals' => $response->getTotals(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener datos de analytics: ' . $e->getMessage());
        }
    }

    /**
     * Get top visited pages
     */
    public function topPages(Request $request): JsonResponse
    {
        try {
            $period = $this->getPeriodFromRequest($request);

            $analytics = $this->getAnalyticsInstance();

            $pages = $analytics->fetchMostVisitedPages($period, 10);

            $formattedPages = $pages->map(function ($page) {
                return [
                    'title' => $page[0] ?? 'Unknown',
                    'url' => $page[1] ?? '#',
                    'views' => (int) ($page[2] ?? 0),
                ];
            })->toArray();

            return response()->json([
                'status' => true,
                'data' => $formattedPages,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener páginas principales: ' . $e->getMessage());
        }
    }

    /**
     * Get top browsers
     */
    public function topBrowsers(Request $request): JsonResponse
    {
        try {
            $period = $this->getPeriodFromRequest($request);

            $analytics = $this->getAnalyticsInstance();

            $browsers = $analytics->fetchTopBrowsers($period, 10);

            $formattedBrowsers = $browsers->map(function ($browser) {
                return [
                    'name' => $browser[0] ?? 'Unknown',
                    'sessions' => (int) ($browser[1] ?? 0),
                ];
            })->toArray();

            return response()->json([
                'status' => true,
                'data' => $formattedBrowsers,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener navegadores: ' . $e->getMessage());
        }
    }

    /**
     * Get top referrers
     */
    public function topReferrers(Request $request): JsonResponse
    {
        try {
            $period = $this->getPeriodFromRequest($request);

            $analytics = $this->getAnalyticsInstance();

            $referrers = $analytics->fetchTopReferrers($period, 10);

            $formattedReferrers = $referrers->map(function ($referrer) {
                return [
                    'source' => $referrer[0] ?? 'Direct',
                    'views' => (int) ($referrer[1] ?? 0),
                ];
            })->toArray();

            return response()->json([
                'status' => true,
                'data' => $formattedReferrers,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener referrers: ' . $e->getMessage());
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
                'status' => true,
                'data' => $data->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error en la query: ' . $e->getMessage());
        }
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

        if (!$propertyId || !$credentials) {
            throw new \Exception('Analytics no está configurado. Por favor, configure la Property ID y las credenciales.');
        }

        return new Analytics($propertyId, $credentials);
    }

    /**
     * Error response
     */
    protected function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $code);
    }
}

<?php

namespace Modules\Erp\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Erp\Models\ErpEndpoint;
use Modules\Erp\Models\ErpEndpointLog;

class DashboardMetricsService
{
    /**
     * Get overall metrics for dashboard
     */
    public function getMetrics(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        return [
            'overview' => $this->getOverviewMetrics(),
            'performance' => $this->getPerformanceMetrics($startDate),
            'trends' => $this->getTrendMetrics($startDate),
            'alerts' => $this->getAlerts(),
            'topEndpoints' => $this->getTopEndpoints(5),
            'recentErrors' => $this->getRecentErrors(10),
            'timeRange' => ['days' => $days, 'startDate' => $startDate],
        ];
    }

    /**
     * Get overview statistics
     */
    private function getOverviewMetrics(): array
    {
        // Single aggregate query over erp_endpoints replaces 4 separate COUNTs.
        $row = ErpEndpoint::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_auto_discovered = 0 THEN 1 ELSE 0 END) as configured')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN deprecated_at IS NOT NULL THEN 1 ELSE 0 END) as deprecated')
            ->first();

        $totalEndpoints = (int) ($row->total ?? 0);
        $configuredEndpoints = (int) ($row->configured ?? 0);

        return [
            'total_endpoints' => $totalEndpoints,
            'configured_endpoints' => $configuredEndpoints,
            'active_endpoints' => (int) ($row->active ?? 0),
            'deprecated_endpoints' => (int) ($row->deprecated ?? 0),
            'tokens_generated' => \Modules\Erp\Models\ErpEndpointToken::count(),
            'unconfigured_endpoints' => $totalEndpoints - $configuredEndpoints,
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(Carbon $startDate): array
    {
        // Single aggregate query replaces 4 separate scans of erp_endpoint_logs.
        $row = ErpEndpointLog::query()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_count')
            ->selectRaw('AVG(execution_time) as avg_time')
            ->selectRaw('MAX(execution_time) as max_time')
            ->first();

        $totalCalls = (int) ($row->total ?? 0);
        $successfulCalls = (int) ($row->success_count ?? 0);
        $failedCalls = $totalCalls - $successfulCalls;
        $successRate = $totalCalls > 0 ? round(($successfulCalls / $totalCalls) * 100, 2) : 0;

        return [
            'total_calls' => $totalCalls,
            'successful_calls' => $successfulCalls,
            'failed_calls' => $failedCalls,
            'success_rate' => $successRate,
            'avg_execution_time' => round((float) ($row->avg_time ?? 0), 2),
            'max_execution_time' => (float) ($row->max_time ?? 0),
        ];
    }

    /**
     * Get trend metrics for comparison
     */
    private function getTrendMetrics(Carbon $startDate): array
    {
        $currentPeriodEnd = Carbon::now();
        $previousPeriodStart = $startDate->copy()->subDays((int) $startDate->diffInDays($currentPeriodEnd));

        // Aggregate both periods in one query using conditional sums.
        $row = ErpEndpointLog::query()
            ->where('created_at', '>=', $previousPeriodStart)
            ->where('created_at', '<=', $currentPeriodEnd)
            ->selectRaw(
                'SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as cur_total,
                 SUM(CASE WHEN created_at <  ? THEN 1 ELSE 0 END) as prev_total,
                 SUM(CASE WHEN created_at >= ? AND success = 1 THEN 1 ELSE 0 END) as cur_success,
                 SUM(CASE WHEN created_at <  ? AND success = 1 THEN 1 ELSE 0 END) as prev_success',
                [$startDate, $startDate, $startDate, $startDate]
            )
            ->first();

        return [
            'calls_trend' => $this->calculateTrend((int) ($row->cur_total ?? 0), (int) ($row->prev_total ?? 0)),
            'success_trend' => $this->calculateTrend((int) ($row->cur_success ?? 0), (int) ($row->prev_success ?? 0)),
        ];
    }

    /**
     * Calculate trend percentage
     */
    private function calculateTrend(int $current, int $previous): array
    {
        if ($previous === 0) {
            return [
                'direction' => $current > 0 ? 'up' : 'flat',
                'percent' => 0,
                'absolute' => $current,
            ];
        }

        $percent = round((($current - $previous) / $previous) * 100, 2);

        return [
            'direction' => $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat'),
            'percent' => abs($percent),
            'absolute' => $current - $previous,
        ];
    }

    /**
     * Get performance alerts
     */
    private function getAlerts(): array
    {
        $alerts = [];

        // Aggregate failure rate in one query.
        $failureRow = ErpEndpointLog::query()
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed')
            ->first();

        $totalCalls = (int) ($failureRow->total ?? 0);
        $failedCalls = (int) ($failureRow->failed ?? 0);
        $failureRate = $totalCalls > 0 ? ($failedCalls / $totalCalls) * 100 : 0;

        if ($failureRate > 20) {
            $alerts[] = [
                'type' => 'warning',
                'priority' => 'high',
                'title' => 'Tasa de error elevada',
                'message' => "Los últimos 7 días tienen {$failureRate}% de tasa de error en las llamadas a endpoints.",
                'action' => 'Revisar logs de errores',
            ];
        }

        // Alert: Slow endpoints — DISTINCT endpoint_id from slow logs only.
        // Previous version eager-loaded every log row from every endpoint into RAM.
        $slowEndpointCount = ErpEndpointLog::query()
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->where('execution_time', '>', 5000) // > 5 seconds
            ->distinct('endpoint_id')
            ->count('endpoint_id');

        if ($slowEndpointCount > 0) {
            $alerts[] = [
                'type' => 'info',
                'priority' => 'medium',
                'title' => 'Endpoints lentos detectados',
                'message' => "{$slowEndpointCount} endpoint(s) registraron tiempos de respuesta > 5 segundos.",
                'action' => 'Optimizar endpoints',
            ];
        }

        // Alert: Deprecated endpoints
        $deprecatedEndpoints = ErpEndpoint::whereNotNull('deprecated_at')
            ->where('deprecated_at', '>', Carbon::now()->subDays(30))
            ->count();

        if ($deprecatedEndpoints > 0) {
            $alerts[] = [
                'type' => 'warning',
                'priority' => 'medium',
                'title' => 'Endpoints deprecated recientemente',
                'message' => "{$deprecatedEndpoints} endpoint(s) fueron marcados como deprecated en los últimos 30 días.",
                'action' => 'Revisar endpoints deprecated',
            ];
        }

        // Alert: Unconfigured endpoints
        $unconfigured = ErpEndpoint::where('is_auto_discovered', true)
            ->where('is_active', false)
            ->count();

        if ($unconfigured > 5) {
            $alerts[] = [
                'type' => 'info',
                'priority' => 'low',
                'title' => 'Endpoints sin configurar',
                'message' => "Aún hay {$unconfigured} endpoint(s) disponibles que no han sido configurados.",
                'action' => 'Configurar endpoints',
            ];
        }

        return $alerts;
    }

    /**
     * Get top endpoints by call count
     */
    private function getTopEndpoints(int $limit = 5): Collection
    {
        // Aggregate everything we need at SQL — no eager loading of full log rows.
        // The previous version loaded every log row into RAM via `with(['logs' => ...])`.
        return ErpEndpoint::query()
            ->withCount([
                'logs',
                'logs as success_calls' => fn ($q) => $q
                    ->where('success', true)
                    ->where('created_at', '>=', Carbon::now()->subDays(30)),
            ])
            ->orderByDesc('logs_count')
            ->limit($limit)
            ->get()
            ->map(fn ($endpoint) => [
                'name' => $endpoint->name,
                'slug' => $endpoint->slug,
                'total_calls' => (int) $endpoint->logs_count,
                'success_calls' => (int) $endpoint->success_calls,
                'success_rate' => $endpoint->logs_count > 0
                    ? round(($endpoint->success_calls / $endpoint->logs_count) * 100, 2)
                    : 0,
            ]);
    }

    /**
     * Get recent error logs
     */
    private function getRecentErrors(int $limit = 10): Collection
    {
        return ErpEndpointLog::where('success', false)
            ->with('endpoint')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'endpoint_name' => $log->endpoint?->name ?? 'Unknown',
                    'error_message' => $log->error_message,
                    'status_code' => $log->status_code,
                    'created_at' => $log->created_at,
                    'execution_time' => $log->execution_time,
                ];
            });
    }

    /**
     * Get usage statistics for chart
     */
    public function getUsageChartData(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        // Group at SQL level so we don't load N days × hundreds of logs into RAM.
        $agg = ErpEndpointLog::query()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as d')
            ->selectRaw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_count')
            ->selectRaw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failure_count')
            ->groupBy('d')
            ->get()
            ->keyBy(fn ($row) => (string) $row->d);

        $dates = collect();
        $successData = collect();
        $failureData = collect();

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($days - $i - 1);
            $dateStr = $date->format('Y-m-d');
            $row = $agg->get($dateStr);

            $dates->push($date->format('d/m'));
            $successData->push((int) ($row->success_count ?? 0));
            $failureData->push((int) ($row->failure_count ?? 0));
        }

        return [
            'labels' => $dates->all(),
            'datasets' => [
                [
                    'label' => 'Exitosas',
                    'data' => $successData->all(),
                    'backgroundColor' => '#13C672',
                    'borderColor' => '#0d9a4f',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Fallidas',
                    'data' => $failureData->all(),
                    'backgroundColor' => '#FA896B',
                    'borderColor' => '#d96544',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    /**
     * Get status distribution data for pie chart
     */
    public function getStatusDistributionData(): array
    {
        $row = ErpEndpoint::query()
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive')
            ->selectRaw('SUM(CASE WHEN deprecated_at IS NOT NULL THEN 1 ELSE 0 END) as deprecated')
            ->first();
        $active = (int) ($row->active ?? 0);
        $inactive = (int) ($row->inactive ?? 0);
        $deprecated = (int) ($row->deprecated ?? 0);

        return [
            'labels' => ['Activos', 'Inactivos', 'Deprecated'],
            'datasets' => [
                [
                    'data' => [$active, $inactive, $deprecated],
                    'backgroundColor' => ['#13C672', '#FFC107', '#DC3545'],
                    'borderColor' => ['#0d9a4f', '#e0a800', '#c82333'],
                    'borderWidth' => 2,
                ],
            ],
        ];
    }

    /**
     * Get method distribution data
     */
    public function getMethodDistributionData(): array
    {
        $methods = ErpEndpoint::select('method')
            ->selectRaw('count(*) as count')
            ->groupBy('method')
            ->pluck('count', 'method');

        return [
            'labels' => $methods->keys()->all(),
            'datasets' => [
                [
                    'data' => $methods->values()->all(),
                    'backgroundColor' => ['#0D6EFD', '#198754', '#6C757D', '#FFC107', '#DC3545'],
                    'borderColor' => ['#0b5ed7', '#157347', '#5c636a', '#e0a800', '#c82333'],
                    'borderWidth' => 2,
                ],
            ],
        ];
    }
}

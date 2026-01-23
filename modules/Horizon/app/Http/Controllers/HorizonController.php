<?php

namespace Modules\Horizon\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Horizon\Services\HorizonStatsService;
use Spatie\Health\Facades\Health;

class HorizonController extends Controller
{
    public function __construct(private HorizonStatsService $statsService)
    {
    }

    /**
     * Display Horizon dashboard
     */
    public function index()
    {
        $this->authorize('view-horizon');

        $stats = $this->statsService->getOverallStats();
        $stats['uptime_formatted'] = $this->formatUptime($stats['uptime'] ?? 0);

        $pageTitle = 'Monitoreo de colas';
        $breadcrumb = 'Configuración / Horizon';

        return view('horizon::settings.index', compact(
            'stats',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Display active jobs
     */
    public function jobs()
    {
        $this->authorize('view-horizon');

        $jobs = $this->statsService->getRecentJobs();

        $pageTitle = 'Trabajos activos';
        $breadcrumb = 'Configuración / Horizon / Trabajos activos';

        return view('horizon::settings.jobs', compact(
            'jobs',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Display failed jobs
     */
    public function failed()
    {
        $this->authorize('view-horizon');

        $failedJobs = $this->statsService->getFailedJobs();
        $failedCount = count($failedJobs);

        $pageTitle = 'Trabajos fallidos';
        $breadcrumb = 'Configuración / Horizon / Trabajos fallidos';

        return view('horizon::settings.failed', compact(
            'failedJobs',
            'failedCount',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Display metrics and charts
     */
    public function metrics()
    {
        $this->authorize('view-horizon');

        $metrics = $this->statsService->getMetrics();
        $throughput = $this->statsService->getThroughputData();

        $pageTitle = 'Métricas';
        $breadcrumb = 'Configuración / Horizon / Métricas';

        return view('horizon::settings.metrics', compact(
            'metrics',
            'throughput',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Format uptime seconds to human-readable format
     */
    private function formatUptime(int $seconds): string
    {
        if ($seconds === 0) {
            return '—';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return "{$days}d {$hours}h";
        }

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }
}

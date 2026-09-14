<?php

namespace Modules\Erp\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Erp\Services\DashboardMetricsService;

class ErpDashboardController extends Controller
{
    protected DashboardMetricsService $metricsService;

    public function __construct(DashboardMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Display the dashboard
     */
    public function index(Request $request)
    {
        $days = $request->get('days', 30);

        $metrics = $this->metricsService->getMetrics($days);
        $usageChartData = $this->metricsService->getUsageChartData($days);
        $statusDistribution = $this->metricsService->getStatusDistributionData();
        $methodDistribution = $this->metricsService->getMethodDistributionData();

        return view('erp::settings.dashboard', compact(
            'metrics',
            'usageChartData',
            'statusDistribution',
            'methodDistribution',
            'days'
        ));
    }

    /**
     * Get API metrics for AJAX calls
     */
    public function getMetricsData(Request $request)
    {
        $days = $request->get('days', 30);
        $metrics = $this->metricsService->getMetrics($days);

        return response()->json($metrics);
    }
}

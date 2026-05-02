<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Social\Services\PerformanceInsightsService;

class PerformanceInsightsController extends Controller
{
    public function __construct(
        protected PerformanceInsightsService $insightsService
    ) {}

    /**
     * Show performance insights dashboard
     */
    public function index(Request $request): View
    {
        $accountId = auth()->user()->account_id;

        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $insights = $this->insightsService->getInsights($accountId, $startDate, $endDate);

        return view('social::insights.index', compact('insights', 'startDate', 'endDate'));
    }

    /**
     * Get insights data as JSON (AJAX)
     */
    public function getData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $accountId = auth()->user()->account_id;

        $insights = $this->insightsService->getInsights(
            $accountId,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $insights,
        ]);
    }

    /**
     * Export insights to CSV
     */
    public function export(Request $request): Response
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $accountId = auth()->user()->account_id;

        $csv = $this->insightsService->exportToCsv(
            $accountId,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $filename = 'performance-insights-'.now()->format('Y-m-d').'.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}

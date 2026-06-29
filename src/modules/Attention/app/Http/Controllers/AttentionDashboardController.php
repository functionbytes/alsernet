<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Attention\Services\AttentionStatisticsService;

class AttentionDashboardController extends Controller
{
    private static function trendLast7DaysByStatus(string $status): array
    {
        $rows = Attention::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d') as date, COUNT(*) as count")
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->where('status', $status)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        return collect(range(6, 0))
            ->map(fn ($d) => now()->subDays($d)->format('Y-m-d'))
            ->map(fn ($date) => ['date' => $date, 'count' => (int) ($rows[$date]->count ?? 0)])
            ->values()
            ->toArray();
    }

    public function index(Request $request): View
    {
        $from = $request->date('from') ?? Carbon::now()->subDays(30);
        $to = $request->date('to') ?? Carbon::now();

        $stats = AttentionStatisticsService::getDashboardStats($from, $to);

        return view('attention::dashboard.index', compact('stats', 'from', 'to'));
    }

    public function chartData(Request $request): JsonResponse
    {
        $from = $request->date('from') ?? Carbon::now()->subDays(30);
        $to = $request->date('to') ?? Carbon::now();

        // Previous period of the same length for comparison
        $duration = $from->diffInSeconds($to);
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subSeconds($duration);

        $status = AttentionStatus::class;

        // Consolidated count for current period
        $currentRow = Attention::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_process
            ', [
                $status::RECEIVED->value,
                $status::IN_PROCESS->value,
            ])->first();

        // Consolidated count for previous period
        $prevRow = Attention::query()
            ->whereBetween('created_at', [$prevFrom, $prevTo])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_process
            ', [
                $status::RECEIVED->value,
                $status::IN_PROCESS->value,
            ])->first();

        return response()->json([
            'total' => (int) $currentRow->total,
            'prev_total' => (int) $prevRow->total,
            'pending' => (int) $currentRow->pending,
            'prev_pending' => (int) $prevRow->pending,
            'in_process' => (int) $currentRow->in_process,
            'prev_in_process' => (int) $prevRow->in_process,
            'by_status' => AttentionStatisticsService::countByStatus($from, $to),
            'by_type' => AttentionStatisticsService::countByType($from, $to),
            'by_department' => AttentionStatisticsService::countByDepartment($from, $to),
            'trend_7days' => AttentionStatisticsService::trendLast7Days(),
            'spark_total' => AttentionStatisticsService::trendLast7Days(),
            'spark_pending' => self::trendLast7DaysByStatus($status::RECEIVED->value),
            'spark_in_process' => self::trendLast7DaysByStatus($status::IN_PROCESS->value),
            'avg_resolution_hours' => AttentionStatisticsService::getAverageResolutionTime($from, $to),
            'sla_compliance' => AttentionStatisticsService::getSlaComplianceRate($from, $to),
            'avg_satisfaction' => AttentionStatisticsService::getAverageSatisfaction($from, $to),
            'top_categories' => AttentionStatisticsService::getTopCategories(10),
        ]);
    }
}

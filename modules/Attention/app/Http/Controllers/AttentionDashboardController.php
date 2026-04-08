<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Attention\Services\AttentionStatisticsService;

class AttentionDashboardController extends Controller
{
    private static function trendLast7DaysByStatus(string $status): array
    {
        $rows = DB::table('attentions')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d') as date, COUNT(*) as count")
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->where('status', $status)
            ->whereNull('deleted_at')
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

        $count = fn ($f, $t, $st = null) => Attention::query()
            ->whereBetween('created_at', [$f, $t])
            ->whereNull('deleted_at')
            ->when($st, fn ($q) => $q->where('status', $st))
            ->count();

        $total = $count($from, $to);
        $prevTotal = $count($prevFrom, $prevTo);
        $pending = $count($from, $to, $status::RECEIVED);
        $prevPending = $count($prevFrom, $prevTo, $status::RECEIVED);
        $inProcess = $count($from, $to, $status::IN_PROCESS);
        $prevInProc = $count($prevFrom, $prevTo, $status::IN_PROCESS);

        return response()->json([
            'total' => $total,
            'prev_total' => $prevTotal,
            'pending' => $pending,
            'prev_pending' => $prevPending,
            'in_process' => $inProcess,
            'prev_in_process' => $prevInProc,
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

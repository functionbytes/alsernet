<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Http\Requests\StatsByDateRequest;
use Modules\Attention\Models\Attention;
use Throwable;

/**
 * Statistics and analytics endpoints — authentication required.
 */
class AttentionStatsApiController extends Controller
{
    /**
     * Get basic statistics
     * GET /api/attentions/stats
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->input('date_from', now()->startOfMonth());
            $dateTo = $request->input('date_to', now()->endOfMonth());

            $statusRow = DB::table('attentions')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNull('deleted_at')
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as received,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_process,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as closed,
                    SUM(CASE WHEN is_anonymous = 1 THEN 1 ELSE 0 END) as anonymous,
                    AVG(CASE WHEN satisfaction_rating IS NOT NULL THEN satisfaction_rating END) as avg_satisfaction
                ', [
                    AttentionStatus::RECEIVED->value,
                    AttentionStatus::IN_PROCESS->value,
                    AttentionStatus::RESOLVED->value,
                    AttentionStatus::CLOSED->value,
                ])->first();

            $byType = DB::table('attentions')
                ->leftJoin('attention_types', 'attentions.type_id', '=', 'attention_types.id')
                ->select('attention_types.name as name', DB::raw('COUNT(*) as count'))
                ->whereBetween('attentions.created_at', [$dateFrom, $dateTo])
                ->whereNull('attentions.deleted_at')
                ->groupBy('attentions.type_id', 'attention_types.name')
                ->orderByDesc('count')
                ->get();

            $byCategory = DB::table('attentions')
                ->leftJoin('attention_categories', 'attentions.category_id', '=', 'attention_categories.id')
                ->select('attention_categories.name as name', DB::raw('COUNT(*) as count'))
                ->whereBetween('attentions.created_at', [$dateFrom, $dateTo])
                ->whereNull('attentions.deleted_at')
                ->groupBy('attentions.category_id', 'attention_categories.name')
                ->orderByDesc('count')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => (int) $statusRow->total,
                    'by_status' => [
                        'received' => (int) $statusRow->received,
                        'in_process' => (int) $statusRow->in_process,
                        'resolved' => (int) $statusRow->resolved,
                        'closed' => (int) $statusRow->closed,
                    ],
                    'by_type' => $byType,
                    'by_category' => $byCategory,
                    'anonymous' => (int) $statusRow->anonymous,
                    'average_satisfaction' => $statusRow->avg_satisfaction ? round($statusRow->avg_satisfaction, 2) : null,
                    'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching stats', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get comprehensive dashboard statistics
     * GET /api/attentions/stats/dashboard
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        try {
            $now = now();

            $totalsRow = Attention::query()->selectRaw('
                COUNT(*) as all_count,
                SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as week,
                SUM(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 ELSE 0 END) as month
            ', [
                $now->toDateString(),
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
                $now->month,
                $now->year,
            ])->first();

            $statusRow = Attention::query()->selectRaw('
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as received,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_process,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as closed
            ', [
                AttentionStatus::RECEIVED->value,
                AttentionStatus::IN_PROCESS->value,
                AttentionStatus::RESOLVED->value,
                AttentionStatus::CLOSED->value,
            ])->first();

            $byType = Attention::query()
                ->leftJoin('attention_types', 'attentions.type_id', '=', 'attention_types.id')
                ->select('attention_types.name as type', 'attention_types.code', DB::raw('COUNT(*) as count'))
                ->groupBy('attentions.type_id', 'attention_types.name', 'attention_types.code')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($item) => [
                    'type' => $item->type ?? 'Sin tipo',
                    'code' => $item->code,
                    'count' => $item->count,
                ]);

            $satisfactionRow = Attention::query()
                ->selectRaw('AVG(satisfaction_rating) as avg_rating, COUNT(CASE WHEN satisfaction_rating IS NOT NULL THEN 1 END) as total_rated')
                ->first();

            $avgResolutionTime = Attention::query()
                ->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours');

            $totalWithSla = Attention::whereNotNull('sla_policy_id')->count();
            $breachedCount = Attention::whereHas('breaches')->distinct('id')->count();
            $slaComplianceRate = $totalWithSla > 0 ? (($totalWithSla - $breachedCount) / $totalWithSla) * 100 : null;

            $topCategories = Attention::query()
                ->leftJoin('attention_categories', 'attentions.category_id', '=', 'attention_categories.id')
                ->select('attention_categories.name as category', DB::raw('COUNT(*) as count'))
                ->whereNotNull('attentions.category_id')
                ->groupBy('attentions.category_id', 'attention_categories.name')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(fn ($item) => ['category' => $item->category ?? 'Sin categoría', 'count' => $item->count]);

            $topDepartments = Attention::query()
                ->leftJoin('attention_departments', 'attentions.department_id', '=', 'attention_departments.id')
                ->select('attention_departments.name as department', DB::raw('COUNT(*) as count'))
                ->whereNotNull('attentions.department_id')
                ->whereIn('attentions.status', [AttentionStatus::RECEIVED->value, AttentionStatus::IN_PROCESS->value])
                ->groupBy('attentions.department_id', 'attention_departments.name')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(fn ($item) => ['department' => $item->department ?? 'Sin departamento', 'count' => $item->count]);

            return response()->json([
                'success' => true,
                'data' => [
                    'totals' => [
                        'all' => (int) $totalsRow->all_count,
                        'today' => (int) $totalsRow->today,
                        'week' => (int) $totalsRow->week,
                        'month' => (int) $totalsRow->month,
                    ],
                    'by_status' => [
                        'received' => (int) $statusRow->received,
                        'in_process' => (int) $statusRow->in_process,
                        'resolved' => (int) $statusRow->resolved,
                        'closed' => (int) $statusRow->closed,
                    ],
                    'by_type' => $byType,
                    'satisfaction' => [
                        'average' => $satisfactionRow->avg_rating ? round($satisfactionRow->avg_rating, 2) : null,
                        'total_rated' => (int) $satisfactionRow->total_rated,
                    ],
                    'performance' => [
                        'avg_resolution_time_hours' => $avgResolutionTime ? round($avgResolutionTime, 2) : null,
                        'sla_compliance_rate' => $slaComplianceRate ? round($slaComplianceRate, 2) : null,
                        'total_with_sla' => $totalWithSla,
                        'breached_count' => $breachedCount,
                    ],
                    'top_categories' => $topCategories,
                    'top_departments' => $topDepartments,
                    'generated_at' => $now->toIso8601String(),
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching dashboard stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas del dashboard',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get statistics grouped by attention type
     * GET /api/attentions/stats/by-type
     */
    public function statsByType(StatsByDateRequest $request): JsonResponse
    {
        try {
            $query = Attention::query()
                ->leftJoin('attention_types', 'attentions.type_id', '=', 'attention_types.id');

            if ($request->filled('date_from')) {
                $query->whereDate('attentions.created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('attentions.created_at', '<=', $request->date_to);
            }

            $stats = $query
                ->select(
                    'attention_types.id as type_id',
                    'attention_types.name as type_name',
                    'attention_types.code as type_code',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(CASE WHEN attentions.resolved_at IS NOT NULL THEN 1 ELSE 0 END) as total_resolved'),
                    DB::raw('AVG(CASE WHEN attentions.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, attentions.created_at, attentions.resolved_at) END) as avg_resolution_minutes'),
                    DB::raw('SUM(CASE WHEN attentions.satisfaction_rating IS NOT NULL THEN 1 ELSE 0 END) as total_rated'),
                    DB::raw('AVG(attentions.satisfaction_rating) as avg_satisfaction')
                )
                ->groupBy('attentions.type_id', 'attention_types.id', 'attention_types.name', 'attention_types.code')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($item) => [
                    'type_id' => $item->type_id,
                    'type_name' => $item->type_name ?? 'Sin tipo',
                    'type_code' => $item->type_code,
                    'count' => (int) $item->count,
                    'avg_resolution_time_hours' => $item->avg_resolution_minutes ? round($item->avg_resolution_minutes / 60, 2) : null,
                    'avg_satisfaction' => $item->avg_satisfaction ? round($item->avg_satisfaction, 2) : null,
                    'total_resolved' => (int) $item->total_resolved,
                    'total_rated' => (int) $item->total_rated,
                ]);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'filters' => [
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching stats by type', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas por tipo',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get statistics grouped by status
     * GET /api/attentions/stats/by-status
     */
    public function statsByStatus(StatsByDateRequest $request): JsonResponse
    {
        try {
            $query = Attention::query();

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $row = $query->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as received,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_process,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as closed,
                AVG(CASE WHEN status = ? THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) END) as avg_time_received,
                AVG(CASE WHEN status = ? THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) END) as avg_time_in_process,
                AVG(CASE WHEN status = ? THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) END) as avg_time_resolved,
                AVG(CASE WHEN status = ? THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) END) as avg_time_closed
            ', [
                AttentionStatus::RECEIVED->value,
                AttentionStatus::IN_PROCESS->value,
                AttentionStatus::RESOLVED->value,
                AttentionStatus::CLOSED->value,
                AttentionStatus::RECEIVED->value,
                AttentionStatus::IN_PROCESS->value,
                AttentionStatus::RESOLVED->value,
                AttentionStatus::CLOSED->value,
            ])->first();

            $total = (int) $row->total;

            $stats = collect(AttentionStatus::cases())
                ->map(function ($status) use ($row, $total) {
                    $count = match ($status) {
                        AttentionStatus::RECEIVED => (int) $row->received,
                        AttentionStatus::IN_PROCESS => (int) $row->in_process,
                        AttentionStatus::RESOLVED => (int) $row->resolved,
                        AttentionStatus::CLOSED => (int) $row->closed,
                    };

                    $avgTime = match ($status) {
                        AttentionStatus::RECEIVED => $row->avg_time_received,
                        AttentionStatus::IN_PROCESS => $row->avg_time_in_process,
                        AttentionStatus::RESOLVED => $row->avg_time_resolved,
                        AttentionStatus::CLOSED => $row->avg_time_closed,
                    };

                    return [
                        'status' => $status->value,
                        'status_label' => $status->label(),
                        'status_color' => $status->color(),
                        'count' => $count,
                        'avg_time_in_status_hours' => $avgTime ? round($avgTime, 2) : null,
                        'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'total' => $total,
                'filters' => [
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching stats by status', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas por estado',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }
}

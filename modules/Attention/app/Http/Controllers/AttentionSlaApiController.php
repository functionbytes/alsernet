<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Models\Attention;
use Throwable;

/**
 * SLA monitoring endpoints — authentication required.
 */
class AttentionSlaApiController extends Controller
{
    /**
     * Get SLA status for a specific peticion
     * GET /api/attentions/{radicado}/sla-status
     */
    public function slaStatus(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)
                ->with(['slaPolicy', 'breaches'])
                ->firstOrFail();

            if (! $attention->slaPolicy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta atención no tiene una política SLA asignada',
                ], 404);
            }

            $policy = $attention->slaPolicy;
            $now = now();
            $createdAt = $attention->created_at;
            $elapsedMinutes = $createdAt->diffInMinutes($now);
            $resolutionTimeLimit = $policy->resolution_time;
            $percentageUsed = $resolutionTimeLimit > 0 ? ($elapsedMinutes / $resolutionTimeLimit) * 100 : 0;

            $slaStatus = match (true) {
                $percentageUsed >= 100 => 'breached',
                $percentageUsed >= 80 => 'warning',
                default => 'on_time',
            };

            $nextMilestone = null;
            if ($percentageUsed < 80) {
                $nextMilestone = [
                    'type' => 'warning',
                    'threshold_percent' => 80,
                    'minutes_remaining' => max(0, ($resolutionTimeLimit * 0.8) - $elapsedMinutes),
                ];
            } elseif ($percentageUsed < 100) {
                $nextMilestone = [
                    'type' => 'breach',
                    'threshold_percent' => 100,
                    'minutes_remaining' => max(0, $resolutionTimeLimit - $elapsedMinutes),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'attention_uid' => $attention->uid,
                    'radicado' => $attention->radicado,
                    'status' => $attention->status->value,
                    'sla_policy' => [
                        'id' => $policy->id,
                        'name' => $policy->name,
                        'resolution_time_minutes' => $policy->resolution_time,
                    ],
                    'time_tracking' => [
                        'created_at' => $createdAt->toIso8601String(),
                        'elapsed_minutes' => round($elapsedMinutes, 2),
                        'elapsed_hours' => round($elapsedMinutes / 60, 2),
                        'limit_minutes' => $resolutionTimeLimit,
                        'limit_hours' => round($resolutionTimeLimit / 60, 2),
                        'percentage_used' => round($percentageUsed, 2),
                    ],
                    'sla_status' => $slaStatus,
                    'has_breaches' => $attention->breaches()->exists(),
                    'breach_count' => $attention->breaches()->count(),
                    'next_milestone' => $nextMilestone,
                    'checked_at' => $now->toIso8601String(),
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching SLA status', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado SLA',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 404);
        }
    }

    /**
     * Get list of peticiones with SLA breaches
     * GET /api/attentions/sla-breaches
     */
    public function slaBreaches(Request $request): JsonResponse
    {
        $request->validate([
            'breach_type' => 'nullable|string|in:response,resolution,closure',
            'department_id' => 'nullable|integer|exists:attention_departments,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $query = Attention::whereHas('breaches')
                ->with(['type', 'category', 'department', 'assignedUser', 'slaPolicy', 'breaches']);

            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('breach_type')) {
                $query->whereHas('breaches', fn ($q) => $q->where('breach_type', $request->breach_type));
            }

            $query->selectRaw('
                attentions.*,
                CASE
                    WHEN attention_sla_policies.resolution_time > 0
                    THEN (
                        (TIMESTAMPDIFF(MINUTE, attentions.created_at, NOW()) - attention_sla_policies.resolution_time)
                        / attention_sla_policies.resolution_time
                    ) * 100
                    ELSE 0
                END as severity_score
            ')
                ->leftJoin('attention_sla_policies', 'attentions.sla_policy_id', '=', 'attention_sla_policies.id')
                ->orderByDesc('severity_score');

            $perPage = min($request->integer('per_page', 15), 100);
            $attentions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $attentions->items(),
                'meta' => [
                    'current_page' => $attentions->currentPage(),
                    'last_page' => $attentions->lastPage(),
                    'per_page' => $attentions->perPage(),
                    'total' => $attentions->total(),
                    'from' => $attentions->firstItem(),
                    'to' => $attentions->lastItem(),
                ],
                'filters' => [
                    'breach_type' => $request->breach_type,
                    'department_id' => $request->department_id,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching SLA breaches', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener incumplimientos SLA',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }
}

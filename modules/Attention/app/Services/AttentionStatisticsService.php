<?php

namespace Modules\Attention\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;

/**
 * Servicio de estadísticas para peticiones
 *
 * Proporciona métricas y análisis sobre peticiones incluyendo dashboard stats,
 * tendencias temporales, compliance de SLA y análisis por diferentes dimensiones.
 */
class AttentionStatisticsService
{
    /**
     * Tiempo de cache en minutos — configurable via config('attention.stats_cache_ttl', 5)
     */
    protected static function cacheTtl(): int
    {
        return (int) config('attention.stats_cache_ttl', 5);
    }

    /**
     * Invalida todas las keys de cache de estadísticas del dashboard
     */
    public static function flushCache(): void
    {
        // Nota: sin Redis no se pueden borrar por wildcard de forma eficiente.
        // En producción con Redis se puede usar Cache::tags(['attention_stats'])->flush();
    }

    /**
     * Obtiene estadísticas completas para el dashboard
     *
     * @param  Carbon|null  $from  Fecha inicial (null = últimos 30 días)
     * @param  Carbon|null  $to  Fecha final (null = ahora)
     * @return array Estadísticas completas del dashboard
     *
     * @throws Exception Si falla la consulta
     */
    public static function getDashboardStats(?Carbon $from = null, ?Carbon $to = null): array
    {
        try {
            $dateRange = self::getDefaultDateRange();
            $from = $from ?? $dateRange['from'];
            $to = $to ?? $dateRange['to'];

            $cacheKey = "attention_dashboard_stats_{$from->timestamp}_{$to->timestamp}";

            return Cache::remember($cacheKey, self::cacheTtl() * 60, function () use ($from, $to) {
                $row = Attention::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->selectRaw('
                        COUNT(*) as total,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_process,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as resolved,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as closed
                    ', [
                        AttentionStatus::RECEIVED->value,
                        AttentionStatus::IN_PROCESS->value,
                        AttentionStatus::RESOLVED->value,
                        AttentionStatus::CLOSED->value,
                    ])->first();

                $total = (int) $row->total;
                $pending = (int) $row->pending;
                $in_process = (int) $row->in_process;
                $resolved = (int) $row->resolved;
                $closed = (int) $row->closed;

                return [
                    'period' => [
                        'from' => $from->toDateString(),
                        'to' => $to->toDateString(),
                    ],
                    'total' => $total,
                    'by_status' => [
                        'pending' => $pending,
                        'in_process' => $in_process,
                        'resolved' => $resolved,
                        'closed' => $closed,
                    ],
                    'metrics' => [
                        'average_satisfaction' => self::getAverageSatisfaction($from, $to),
                        'average_resolution_time' => self::getAverageResolutionTime($from, $to),
                        'sla_compliance_rate' => self::getSlaComplianceRate($from, $to),
                    ],
                    'top_categories' => self::getTopCategories(5),
                    'by_type' => self::getStatsByType($from, $to),
                    'generated_at' => now()->toDateTimeString(),
                ];
            });
        } catch (Exception $e) {
            Log::error('Dashboard stats failed', ['error' => $e->getMessage()]);
            throw new Exception('Error al obtener estadísticas del dashboard: '.$e->getMessage());
        }
    }

    /**
     * Obtiene estadísticas por tipo de peticiones
     *
     * @param  Carbon|null  $from  Fecha inicial
     * @param  Carbon|null  $to  Fecha final
     * @return array Estadísticas por tipo
     *
     * @throws Exception Si falla la consulta
     */
    public static function getStatsByType(?Carbon $from = null, ?Carbon $to = null): array
    {
        try {
            $dateRange = self::getDefaultDateRange();
            $from = $from ?? $dateRange['from'];
            $to = $to ?? $dateRange['to'];

            $cacheKey = "attention_stats_by_type_{$from->timestamp}_{$to->timestamp}";

            return Cache::remember($cacheKey, self::cacheTtl() * 60, function () use ($from, $to) {
                $stats = Attention::query()
                    ->leftJoin('attention_types', 'attentions.type_id', '=', 'attention_types.id')
                    ->select('attention_types.name as type', DB::raw('COUNT(*) as count'))
                    ->whereBetween('attentions.created_at', [$from, $to])
                    ->groupBy('attentions.type_id', 'attention_types.name')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [($item->type ?? 'Sin tipo') => $item->count];
                    })
                    ->toArray();

                return $stats;
            });
        } catch (Exception $e) {
            Log::error('Stats by type failed', ['error' => $e->getMessage()]);
            throw new Exception('Error al obtener estadísticas por tipo: '.$e->getMessage());
        }
    }

    /**
     * Obtiene estadísticas por estado de peticiones
     *
     * @param  Carbon|null  $from  Fecha inicial
     * @param  Carbon|null  $to  Fecha final
     * @return array Estadísticas por estado
     *
     * @throws Exception Si falla la consulta
     */
    public static function getStatsByStatus(?Carbon $from = null, ?Carbon $to = null): array
    {
        try {
            $dateRange = self::getDefaultDateRange();
            $from = $from ?? $dateRange['from'];
            $to = $to ?? $dateRange['to'];

            $cacheKey = "attention_stats_by_status_{$from->timestamp}_{$to->timestamp}";

            return Cache::remember($cacheKey, self::cacheTtl() * 60, function () use ($from, $to) {
                $stats = Attention::query()
                    ->select('status', DB::raw('COUNT(*) as count'))
                    ->whereBetween('created_at', [$from, $to])
                    ->groupBy('status')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->status => $item->count];
                    })
                    ->toArray();

                return $stats;
            });
        } catch (Exception $e) {
            Log::error('Stats by status failed', ['error' => $e->getMessage()]);
            throw new Exception('Error al obtener estadísticas por estado: '.$e->getMessage());
        }
    }

    /**
     * Obtiene estadísticas por departamento
     *
     * @param  Carbon|null  $from  Fecha inicial
     * @param  Carbon|null  $to  Fecha final
     * @return array Estadísticas por departamento
     *
     * @throws Exception Si falla la consulta
     */
    public static function getStatsByDepartment(?Carbon $from = null, ?Carbon $to = null): array
    {
        try {
            $dateRange = self::getDefaultDateRange();
            $from = $from ?? $dateRange['from'];
            $to = $to ?? $dateRange['to'];

            $cacheKey = "attention_stats_by_department_{$from->timestamp}_{$to->timestamp}";

            return Cache::remember($cacheKey, self::cacheTtl() * 60, function () use ($from, $to) {
                $stats = Attention::query()
                    ->leftJoin('attention_departments', 'attentions.department_id', '=', 'attention_departments.id')
                    ->select(
                        'attention_departments.name as department_name',
                        'attentions.department_id',
                        DB::raw('COUNT(*) as total'),
                        DB::raw('SUM(CASE WHEN attentions.status = \''.AttentionStatus::RESOLVED->value.'\' THEN 1 ELSE 0 END) as resolved'),
                        DB::raw('SUM(CASE WHEN attentions.status = \''.AttentionStatus::CLOSED->value.'\' THEN 1 ELSE 0 END) as closed')
                    )
                    ->whereBetween('attentions.created_at', [$from, $to])
                    ->groupBy('attentions.department_id', 'attention_departments.name')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'department_id' => $item->department_id,
                            'department_name' => $item->department_name ?? 'Sin asignar',
                            'total' => $item->total,
                            'resolved' => $item->resolved,
                            'closed' => $item->closed,
                            'resolution_rate' => $item->total > 0
                                ? round(($item->resolved / $item->total) * 100, 2)
                                : 0,
                        ];
                    })
                    ->toArray();

                return $stats;
            });
        } catch (Exception $e) {
            Log::error('Stats by department failed', ['error' => $e->getMessage()]);
            throw new Exception('Error al obtener estadísticas por departamento: '.$e->getMessage());
        }
    }

    /**
     * Obtiene promedio de satisfacción del cliente
     *
     * @param  Carbon|null  $from  Fecha inicial
     * @param  Carbon|null  $to  Fecha final
     * @return float Promedio de satisfacción (0-5)
     *
     * @throws Exception Si falla la consulta
     */
    public static function getAverageSatisfaction(?Carbon $from = null, ?Carbon $to = null): float
    {
        try {
            $dateRange = self::getDefaultDateRange();
            $from = $from ?? $dateRange['from'];
            $to = $to ?? $dateRange['to'];

            $cacheKey = "attention_avg_satisfaction_{$from->timestamp}_{$to->timestamp}";

            return Cache::remember($cacheKey, self::cacheTtl() * 60, function () use ($from, $to) {
                $average = Attention::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->whereNotNull('satisfaction_rating')
                    ->avg('satisfaction_rating');

                return round($average ?? 0, 2);
            });
        } catch (Exception $e) {
            Log::error('Average satisfaction failed', ['error' => $e->getMessage()]);
            throw new Exception('Error al calcular satisfacción promedio: '.$e->getMessage());
        }
    }

    /**
     * Obtiene tiempo promedio de resolución en horas
     *
     * @param  Carbon|null  $from  Fecha inicial
     * @param  Carbon|null  $to  Fecha final
     * @return float Tiempo promedio en horas
     *
     * @throws Exception Si falla la consulta
     */
    public static function getAverageResolutionTime(?Carbon $from = null, ?Carbon $to = null): float
    {
        try {
            $dateRange = self::getDefaultDateRange();
            $from = $from ?? $dateRange['from'];
            $to = $to ?? $dateRange['to'];

            $cacheKey = "attention_avg_resolution_time_{$from->timestamp}_{$to->timestamp}";

            return Cache::remember($cacheKey, self::cacheTtl() * 60, function () use ($from, $to) {
                $average = Attention::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->whereNotNull('resolved_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                    ->first();

                return round($average->avg_hours ?? 0, 2);
            });
        } catch (Exception $e) {
            Log::error('Average resolution time failed', ['error' => $e->getMessage()]);
            throw new Exception('Error al calcular tiempo promedio de resolución: '.$e->getMessage());
        }
    }

    /**
     * Obtiene tasa de cumplimiento de SLA
     *
     * @param  Carbon|null  $from  Fecha inicial
     * @param  Carbon|null  $to  Fecha final
     * @return float Porcentaje de cumplimiento (0-100)
     *
     * @throws Exception Si falla la consulta
     */
    public static function getSlaComplianceRate(?Carbon $from = null, ?Carbon $to = null): float
    {
        try {
            $dateRange = self::getDefaultDateRange();
            $from = $from ?? $dateRange['from'];
            $to = $to ?? $dateRange['to'];

            $cacheKey = "attention_sla_compliance_{$from->timestamp}_{$to->timestamp}";

            return Cache::remember($cacheKey, self::cacheTtl() * 60, function () use ($from, $to) {
                $total = Attention::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->whereNotNull('resolved_at')
                    ->count();

                if ($total === 0) {
                    return 0.0;
                }

                // Considera cumplido si se resolvió dentro de los 5 días hábiles
                $compliant = Attention::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->whereNotNull('resolved_at')
                    ->whereRaw('TIMESTAMPDIFF(DAY, created_at, resolved_at) <= 5')
                    ->count();

                return round(($compliant / $total) * 100, 2);
            });
        } catch (Exception $e) {
            Log::error('SLA compliance rate failed', ['error' => $e->getMessage()]);
            throw new Exception('Error al calcular tasa de cumplimiento SLA: '.$e->getMessage());
        }
    }

    /**
     * Obtiene las categorías más frecuentes
     *
     * @param  int  $limit  Cantidad de categorías a retornar
     * @return Collection Colección de categorías con contadores
     *
     * @throws Exception Si falla la consulta
     */
    public static function getTopCategories(int $limit = 5): Collection
    {
        try {
            $cacheKey = "attention_top_categories_{$limit}";

            return Cache::remember($cacheKey, self::cacheTtl() * 60, function () use ($limit) {
                return Attention::query()
                    ->leftJoin('attention_categories', 'attentions.category_id', '=', 'attention_categories.id')
                    ->select('attention_categories.name as category', DB::raw('COUNT(*) as count'))
                    ->whereNotNull('attentions.category_id')
                    ->groupBy('attentions.category_id', 'attention_categories.name')
                    ->orderByDesc('count')
                    ->limit($limit)
                    ->get();
            });
        } catch (Exception $e) {
            Log::error('Top categories failed', ['error' => $e->getMessage()]);
            throw new Exception('Error al obtener categorías principales: '.$e->getMessage());
        }
    }

    /**
     * Obtiene tendencia temporal de peticiones
     *
     * @param  string  $period  Período de agrupación (day, week, month)
     * @param  int  $periods  Cantidad de períodos a retornar
     * @return array Array con datos de tendencia
     *
     * @throws Exception Si falla la consulta
     */
    public static function getTimeTrend(string $period = 'day', int $periods = 30): array
    {
        try {
            $cacheKey = "attention_time_trend_{$period}_{$periods}";

            return Cache::remember($cacheKey, self::cacheTtl() * 60, function () use ($period, $periods) {
                $format = match ($period) {
                    'day' => '%Y-%m-%d',
                    'week' => '%Y-%u',
                    'month' => '%Y-%m',
                    default => '%Y-%m-%d'
                };

                $startDate = match ($period) {
                    'day' => now()->subDays($periods),
                    'week' => now()->subWeeks($periods),
                    'month' => now()->subMonths($periods),
                    default => now()->subDays($periods)
                };

                $trend = Attention::query()
                    ->select(
                        DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
                        DB::raw('COUNT(*) as count'),
                        DB::raw('SUM(CASE WHEN status = \''.AttentionStatus::RESOLVED->value.'\' THEN 1 ELSE 0 END) as resolved')
                    )
                    ->where('created_at', '>=', $startDate)
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get()
                    ->toArray();

                return $trend;
            });
        } catch (Exception $e) {
            Log::error('Time trend failed', ['error' => $e->getMessage()]);
            throw new Exception('Error al obtener tendencia temporal: '.$e->getMessage());
        }
    }

    /**
     * Obtiene estadísticas de un usuario específico
     *
     * @param  int  $userId  ID del usuario
     * @return array Estadísticas del usuario
     *
     * @throws Exception Si falla la consulta
     */
    public static function getUserStats(int $userId): array
    {
        try {
            $cacheKey = "attention_user_stats_{$userId}";

            return Cache::remember($cacheKey, self::cacheTtl() * 60, function () use ($userId) {
                $row = Attention::query()
                    ->where('assigned_user_id', $userId)
                    ->selectRaw('
                        COUNT(*) as total,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as resolved,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as closed,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_process
                    ', [
                        AttentionStatus::RESOLVED->value,
                        AttentionStatus::CLOSED->value,
                        AttentionStatus::RECEIVED->value,
                        AttentionStatus::IN_PROCESS->value,
                    ])->first();

                $avgResolutionTime = Attention::query()
                    ->where('assigned_user_id', $userId)
                    ->whereNotNull('resolved_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                    ->first();

                return [
                    'user_id' => $userId,
                    'total_assigned' => (int) $row->total,
                    'resolved' => (int) $row->resolved,
                    'closed' => (int) $row->closed,
                    'pending' => (int) $row->pending,
                    'in_process' => (int) $row->in_process,
                    'resolution_rate' => $row->total > 0 ? round(($row->resolved / $row->total) * 100, 2) : 0,
                    'average_resolution_time_hours' => round($avgResolutionTime->avg_hours ?? 0, 2),
                    'generated_at' => now()->toDateTimeString(),
                ];
            });
        } catch (Exception $e) {
            Log::error('User stats failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw new Exception('Error al obtener estadísticas del usuario: '.$e->getMessage());
        }
    }

    /**
     * Retorna conteo por estado en formato [{label, count}] para gráficos.
     */
    public static function countByStatus(?Carbon $from = null, ?Carbon $to = null): array
    {
        $dateRange = self::getDefaultDateRange();
        $from = $from ?? $dateRange['from'];
        $to = $to ?? $dateRange['to'];

        $labelMap = [
            AttentionStatus::RECEIVED->value => 'Recibido',
            AttentionStatus::IN_PROCESS->value => 'En proceso',
            AttentionStatus::RESOLVED->value => 'Resuelto',
            AttentionStatus::CLOSED->value => 'Cerrado',
        ];

        return Attention::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'label' => $labelMap[$row->status] ?? $row->status,
                'count' => (int) $row->count,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Retorna conteo por tipo en formato [{label, count}] para gráficos.
     */
    public static function countByType(?Carbon $from = null, ?Carbon $to = null): array
    {
        $dateRange = self::getDefaultDateRange();
        $from = $from ?? $dateRange['from'];
        $to = $to ?? $dateRange['to'];

        return Attention::query()
            ->leftJoin('attention_types', 'attentions.type_id', '=', 'attention_types.id')
            ->select('attention_types.name as label', DB::raw('COUNT(*) as count'))
            ->whereBetween('attentions.created_at', [$from, $to])
            ->groupBy('attentions.type_id', 'attention_types.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label ?? 'Sin tipo',
                'count' => (int) $row->count,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Retorna conteo por departamento en formato [{label, count}] para gráficos.
     */
    public static function countByDepartment(?Carbon $from = null, ?Carbon $to = null): array
    {
        $dateRange = self::getDefaultDateRange();
        $from = $from ?? $dateRange['from'];
        $to = $to ?? $dateRange['to'];

        return Attention::query()
            ->leftJoin('attention_departments', 'attentions.department_id', '=', 'attention_departments.id')
            ->select('attention_departments.name as label', DB::raw('COUNT(*) as count'))
            ->whereBetween('attentions.created_at', [$from, $to])
            ->groupBy('attentions.department_id', 'attention_departments.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label ?? 'Sin asignar',
                'count' => (int) $row->count,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Retorna conteo por día para los últimos 7 días en formato [{date, count}].
     *
     * @return array<int, array{date: string, count: int}>
     */
    public static function trendLast7Days(): array
    {
        $rows = Attention::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d') as date, COUNT(*) as count")
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill all 7 days, including days with zero attentions
        return collect(range(6, 0))
            ->map(fn ($daysAgo) => now()->subDays($daysAgo)->format('Y-m-d'))
            ->map(fn ($date) => [
                'date' => $date,
                'count' => (int) ($rows[$date]->count ?? 0),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Obtiene rango de fechas por defecto (últimos 30 días)
     *
     * @return array Array con 'from' y 'to' Carbon instances
     */
    protected static function getDefaultDateRange(): array
    {
        return [
            'from' => now()->subDays(30)->startOfDay(),
            'to' => now()->endOfDay(),
        ];
    }
}

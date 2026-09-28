<?php

namespace Modules\HelpdeskChatFlow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowAnalyticsService;

/**
 * Dashboard de métricas de un flow (resumen, abandono por nodo, IA, CSAT, A/B),
 * cacheado por (flow, ventana) e invalidado por InvalidateFlowAnalyticsCache.
 */
class ChatFlowAnalyticsController extends Controller
{
    /** Selectable analytics windows (days => label); 0 = all-time. Keys shared with cache invalidation. */
    public const RANGE_OPTIONS = [
        7 => 'Últimos 7 días',
        30 => 'Últimos 30 días',
        90 => 'Últimos 90 días',
        365 => 'Último año',
        0 => 'Todo el histórico',
    ];

    public static function analyticsCacheKey(int $flowId, int $days): string
    {
        return "helpdeskchatflow:analytics:{$flowId}:{$days}";
    }

    public function show(Request $request, ChatFlow $chatFlow, ChatFlowAnalyticsService $analytics): View
    {
        $this->authorize('view', $chatFlow);

        $range = $this->resolveAnalyticsRange($request);

        // The metric blocks scan the flow's whole session history; cache them per
        // (flow, days). Invalidated by InvalidateFlowAnalyticsCache when a session
        // of this flow completes, so the numbers stay fresh without re-querying on
        // every dashboard open. TTL is a safety net for other mutations.
        $metrics = Cache::remember(
            self::analyticsCacheKey($chatFlow->id, $range['days']),
            now()->addMinutes(15),
            function () use ($chatFlow, $range, $analytics): array {
                $from = $range['from'];
                $summary = $analytics->buildSummary($chatFlow, $from);
                $csat = $analytics->buildCsatMetrics($chatFlow, $from);
                $aiMetrics = $analytics->buildAiMetrics($chatFlow, $from);
                $nodeLatency = $analytics->buildNodeLatency($chatFlow, $from);

                return [
                    'summary' => $summary,
                    'dropOff' => $analytics->buildDropOff($chatFlow, $from),
                    'aiMetrics' => $aiMetrics,
                    'csat' => $csat,
                    'csatTrend' => $analytics->buildCsatTrend($chatFlow, $from),
                    'nodeLatency' => $nodeLatency,
                    'httpAlerts' => $analytics->buildHttpFailureAlerts($nodeLatency),
                    'comparison' => $analytics->buildAbComparison($chatFlow, $from, [
                        'summary' => $summary,
                        'csat' => $csat,
                        'ai' => $aiMetrics,
                    ]),
                ];
            }
        );

        return view('chatflow::analytics', array_merge(
            ['chatFlow' => $chatFlow, 'range' => $range],
            $metrics
        ));
    }

    /**
     * Resolve the analytics time window from the request. Defaults to the last
     * 30 days so high-volume flows don't full-scan their session history; an
     * "all time" option (`days=0`) recovers the previous unbounded behaviour.
     *
     * @return array{days: int, from: ?Carbon, options: array<int, string>}
     */
    private function resolveAnalyticsRange(Request $request): array
    {
        $options = self::RANGE_OPTIONS;

        $days = (int) $request->input('days', 30);
        if (! array_key_exists($days, $options)) {
            $days = 30;
        }

        return [
            'days' => $days,
            'from' => $days > 0 ? now()->subDays($days)->startOfDay() : null,
            'options' => $options,
        ];
    }
}

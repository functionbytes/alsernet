<?php

namespace Modules\Engagement\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\TriggerRule;
use Modules\Engagement\Models\VisitorScore;
use Modules\Helpdesk\Models\Inbox;

class AnalyticsController extends Controller
{
    private const CACHE_TTL = 180;

    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.events.view')->only('page', 'overview', 'eventsByDay', 'segmentDistribution', 'topEvents', 'triggerPerformance');
    }

    public function page(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get(['id', 'name']);

        return view('engagement::managers.analytics.index', compact('inboxes'));
    }

    /**
     * KPIs cabecera: visitantes únicos, sesiones, score medio, % hot.
     */
    public function overview(Request $request): JsonResponse
    {
        $inboxId = (int) $request->input('inbox_id', 0);
        $days = $this->days($request);

        $data = Cache::remember("engagement:analytics:overview:{$inboxId}:{$days}", self::CACHE_TTL, function () use ($inboxId, $days) {
            $from = now()->subDays($days);

            $eventsQuery = Event::query()
                ->when($inboxId, fn ($q) => $q->forInbox($inboxId))
                ->where('occurred_at', '>=', $from);

            $uniqueVisitors = (clone $eventsQuery)->distinct('session_token')->count('session_token');
            $totalEvents = (clone $eventsQuery)->count();

            $scoreStats = VisitorScore::query()
                ->when($inboxId, fn ($q) => $q->where('inbox_id', $inboxId))
                ->where('updated_at', '>=', $from)
                ->selectRaw('AVG(score) as avg_score, COUNT(*) as total, SUM(CASE WHEN segment = "hot" THEN 1 ELSE 0 END) as hot')
                ->first();

            return [
                'unique_visitors' => $uniqueVisitors,
                'total_events' => $totalEvents,
                'avg_score' => round((float) ($scoreStats->avg_score ?? 0), 1),
                'hot_visitors' => (int) ($scoreStats->hot ?? 0),
                'hot_pct' => $scoreStats?->total ? round(($scoreStats->hot / $scoreStats->total) * 100, 1) : 0,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Serie temporal: eventos por día.
     */
    public function eventsByDay(Request $request): JsonResponse
    {
        $inboxId = (int) $request->input('inbox_id', 0);
        $days = $this->days($request);

        $data = Cache::remember("engagement:analytics:events_by_day:{$inboxId}:{$days}", self::CACHE_TTL, function () use ($inboxId, $days) {
            return Event::query()
                ->when($inboxId, fn ($q) => $q->forInbox($inboxId))
                ->where('occurred_at', '>=', now()->subDays($days))
                ->selectRaw('DATE(occurred_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->orderBy('day')
                ->get();
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Distribución cold/warm/hot.
     */
    public function segmentDistribution(Request $request): JsonResponse
    {
        $inboxId = (int) $request->input('inbox_id', 0);

        $data = Cache::remember("engagement:analytics:segments:{$inboxId}", self::CACHE_TTL, function () use ($inboxId) {
            $rows = VisitorScore::query()
                ->when($inboxId, fn ($q) => $q->where('inbox_id', $inboxId))
                ->selectRaw('segment, COUNT(*) as count')
                ->groupBy('segment')
                ->get()
                ->keyBy('segment');

            return [
                'cold' => (int) ($rows->get('cold')->count ?? 0),
                'warm' => (int) ($rows->get('warm')->count ?? 0),
                'hot' => (int) ($rows->get('hot')->count ?? 0),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Top eventos por nombre.
     */
    public function topEvents(Request $request): JsonResponse
    {
        $inboxId = (int) $request->input('inbox_id', 0);
        $days = $this->days($request);

        $data = Cache::remember("engagement:analytics:top_events:{$inboxId}:{$days}", self::CACHE_TTL, function () use ($inboxId, $days) {
            return Event::query()
                ->when($inboxId, fn ($q) => $q->forInbox($inboxId))
                ->where('occurred_at', '>=', now()->subDays($days))
                ->selectRaw('event_name, COUNT(*) as count')
                ->groupBy('event_name')
                ->orderByDesc('count')
                ->limit(10)
                ->get();
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Stats de triggers: cuántas veces ha disparado cada uno.
     */
    public function triggerPerformance(Request $request): JsonResponse
    {
        $inboxId = (int) $request->input('inbox_id', 0);
        $days = $this->days($request);

        $data = Cache::remember("engagement:analytics:trigger_perf:{$inboxId}:{$days}", self::CACHE_TTL, function () use ($inboxId, $days) {
            $fired = Event::query()
                ->when($inboxId, fn ($q) => $q->forInbox($inboxId))
                ->where('event_name', 'trigger_fired')
                ->where('occurred_at', '>=', now()->subDays($days))
                ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.ruleId')) as rule_id, COUNT(*) as count")
                ->groupBy('rule_id')
                ->get()
                ->keyBy('rule_id');

            $rules = TriggerRule::query()
                ->when($inboxId, fn ($q) => $q->forInbox($inboxId))
                ->get(['id', 'name', 'is_active']);

            return $rules->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'is_active' => $r->is_active,
                'fires' => (int) ($fired->get($r->id)->count ?? 0),
            ])->values();
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    private function days(Request $request): int
    {
        return max(1, min(365, (int) $request->input('days', 30)));
    }
}

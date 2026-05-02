<?php

namespace Modules\Engagement\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\TriggerRule;
use Modules\Engagement\Models\VisitorScore;
use Modules\Helpdesk\Models\Inbox;

class AnalyticsController extends Controller
{
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
        $from = $this->parseFrom($request);

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

        return response()->json([
            'success' => true,
            'data' => [
                'unique_visitors' => $uniqueVisitors,
                'total_events' => $totalEvents,
                'avg_score' => round((float) ($scoreStats->avg_score ?? 0), 1),
                'hot_visitors' => (int) ($scoreStats->hot ?? 0),
                'hot_pct' => $scoreStats?->total ? round(($scoreStats->hot / $scoreStats->total) * 100, 1) : 0,
            ],
        ]);
    }

    /**
     * Serie temporal: eventos por día.
     */
    public function eventsByDay(Request $request): JsonResponse
    {
        $inboxId = (int) $request->input('inbox_id', 0);
        $from = $this->parseFrom($request);

        $rows = Event::query()
            ->when($inboxId, fn ($q) => $q->forInbox($inboxId))
            ->where('occurred_at', '>=', $from)
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    /**
     * Distribución cold/warm/hot.
     */
    public function segmentDistribution(Request $request): JsonResponse
    {
        $inboxId = (int) $request->input('inbox_id', 0);

        $rows = VisitorScore::query()
            ->when($inboxId, fn ($q) => $q->where('inbox_id', $inboxId))
            ->selectRaw('segment, COUNT(*) as count')
            ->groupBy('segment')
            ->get()
            ->keyBy('segment');

        return response()->json([
            'success' => true,
            'data' => [
                'cold' => (int) ($rows->get('cold')->count ?? 0),
                'warm' => (int) ($rows->get('warm')->count ?? 0),
                'hot' => (int) ($rows->get('hot')->count ?? 0),
            ],
        ]);
    }

    /**
     * Top eventos por nombre.
     */
    public function topEvents(Request $request): JsonResponse
    {
        $inboxId = (int) $request->input('inbox_id', 0);
        $from = $this->parseFrom($request);

        $rows = Event::query()
            ->when($inboxId, fn ($q) => $q->forInbox($inboxId))
            ->where('occurred_at', '>=', $from)
            ->selectRaw('event_name, COUNT(*) as count')
            ->groupBy('event_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    /**
     * Stats de triggers: cuántas veces ha disparado cada uno.
     */
    public function triggerPerformance(Request $request): JsonResponse
    {
        $inboxId = (int) $request->input('inbox_id', 0);
        $from = $this->parseFrom($request);

        $fired = Event::query()
            ->when($inboxId, fn ($q) => $q->forInbox($inboxId))
            ->where('event_name', 'trigger_fired')
            ->where('occurred_at', '>=', $from)
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.ruleId')) as rule_id, COUNT(*) as count")
            ->groupBy('rule_id')
            ->get()
            ->keyBy('rule_id');

        $rules = TriggerRule::query()
            ->when($inboxId, fn ($q) => $q->forInbox($inboxId))
            ->get(['id', 'name', 'is_active']);

        return response()->json([
            'success' => true,
            'data' => $rules->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'is_active' => $r->is_active,
                'fires' => (int) ($fired->get($r->id)->count ?? 0),
            ])->values(),
        ]);
    }

    private function parseFrom(Request $request): Carbon
    {
        $days = (int) $request->input('days', 30);
        $days = max(1, min(365, $days));

        return now()->subDays($days);
    }
}

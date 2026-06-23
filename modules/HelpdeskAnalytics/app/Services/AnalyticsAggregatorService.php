<?php

namespace Modules\HelpdeskAnalytics\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\CsatRating;

/**
 * Read-only cross-channel analytics aggregator for the Helpdesk inbox
 * (web/email/whatsapp/facebook/instagram conversations). Every method uses
 * grouped/aggregate SQL (no N+1) and is cached briefly. Consolidates the metrics
 * previously scattered across the CSAT/Trends/Heatmap/AgentPerformance reports.
 */
class AnalyticsAggregatorService
{
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly HealthScoreBatchService $healthScores,
    ) {}

    /**
     * @return array{conversations: int, closed: int, open: int, avg_first_response_seconds: int, csat_avg: float}
     */
    public function overview(Carbon $from, Carbon $to): array
    {
        return $this->remember('overview', $from, $to, function () use ($from, $to): array {
            $row = DB::connection('helpdesk')
                ->table('helpdesk_conversations')
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('
                    COUNT(*) as conversations,
                    SUM(CASE WHEN closed_at IS NOT NULL THEN 1 ELSE 0 END) as closed,
                    AVG(CASE WHEN first_response_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, first_response_at) END) as avg_first_response
                ')
                ->first();

            $open = DB::connection('helpdesk')
                ->table('helpdesk_conversations')
                ->whereNull('deleted_at')
                ->whereNull('closed_at')
                ->count();

            $csatAvg = CsatRating::query()
                ->whereBetween('answered_at', [$from, $to])
                ->whereNotNull('answered_at')
                ->avg('rating');

            return [
                'conversations' => (int) ($row->conversations ?? 0),
                'closed' => (int) ($row->closed ?? 0),
                'open' => (int) $open,
                'avg_first_response_seconds' => (int) round((float) ($row->avg_first_response ?? 0)),
                'csat_avg' => round((float) ($csatAvg ?? 0), 2),
            ];
        });
    }

    /**
     * @return array<int, array{channel: string, count: int}>
     */
    public function channelDistribution(Carbon $from, Carbon $to): array
    {
        return $this->remember('channels', $from, $to, function () use ($from, $to): array {
            return DB::connection('helpdesk')
                ->table('helpdesk_conversations')
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('COALESCE(channel, \'web\') as channel, COUNT(*) as count')
                ->groupBy('channel')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($r): array => ['channel' => (string) $r->channel, 'count' => (int) $r->count])
                ->all();
        });
    }

    /**
     * @return array<int, array{name: string, closed_count: int, csat_avg: float, avg_response_seconds: int, message_count: int}>
     */
    public function agentPerformance(Carbon $from, Carbon $to): array
    {
        return $this->remember('agents', $from, $to, function () use ($from, $to): array {
            $agentRows = DB::connection('helpdesk')
                ->table('helpdesk_conversations as c')
                ->whereBetween('c.closed_at', [$from, $to])
                ->whereNotNull('c.assignee_id')
                ->selectRaw('c.assignee_id, COUNT(*) as closed_count, AVG(CASE WHEN c.first_response_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, c.created_at, c.first_response_at) END) as avg_response_sec')
                ->groupBy('c.assignee_id')
                ->get();

            $agentIds = $agentRows->pluck('assignee_id')->filter()->all();

            if ($agentIds === []) {
                return [];
            }

            $csatByAgent = CsatRating::query()
                ->whereIn('agent_id', $agentIds)
                ->whereBetween('answered_at', [$from, $to])
                ->whereNotNull('answered_at')
                ->groupBy('agent_id')
                ->selectRaw('agent_id, AVG(rating) as csat_avg')
                ->pluck('csat_avg', 'agent_id');

            $messagesByAgent = DB::connection('helpdesk')
                ->table('helpdesk_conversation_items')
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('user_id')
                ->where('type', 'message')
                ->groupBy('user_id')
                ->selectRaw('user_id, COUNT(*) as msg_count')
                ->pluck('msg_count', 'user_id');

            $users = User::whereIn('id', $agentIds)->get(['id', 'firstname', 'lastname'])->keyBy('id');

            return $agentRows->map(fn ($row): array => [
                'name' => ($u = $users->get($row->assignee_id))
                    ? (trim("{$u->firstname} {$u->lastname}") ?: "Agente #{$row->assignee_id}")
                    : "Agente #{$row->assignee_id}",
                'closed_count' => (int) $row->closed_count,
                'csat_avg' => round((float) ($csatByAgent[$row->assignee_id] ?? 0), 2),
                'avg_response_seconds' => (int) round((float) ($row->avg_response_sec ?? 0)),
                'message_count' => (int) ($messagesByAgent[$row->assignee_id] ?? 0),
            ])->sortByDesc('closed_count')->values()->all();
        });
    }

    /**
     * @return array<int, array{date: string, created: int, closed: int}>
     */
    public function trends(Carbon $from, Carbon $to): array
    {
        return $this->remember('trends', $from, $to, function () use ($from, $to): array {
            $created = DB::connection('helpdesk')
                ->table('helpdesk_conversations')
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('DATE(created_at) as d, COUNT(*) as cnt')
                ->groupBy('d')
                ->pluck('cnt', 'd');

            $closed = DB::connection('helpdesk')
                ->table('helpdesk_conversations')
                ->whereNull('deleted_at')
                ->whereBetween('closed_at', [$from, $to])
                ->selectRaw('DATE(closed_at) as d, COUNT(*) as cnt')
                ->groupBy('d')
                ->pluck('cnt', 'd');

            $days = [];
            for ($day = $from->copy()->startOfDay(); $day->lessThanOrEqualTo($to); $day->addDay()) {
                $key = $day->format('Y-m-d');
                $days[] = [
                    'date' => $key,
                    'created' => (int) ($created[$key] ?? 0),
                    'closed' => (int) ($closed[$key] ?? 0),
                ];
            }

            return $days;
        });
    }

    /**
     * Day-of-week (1=Mon..7=Sun) x hour (0..23) matrix of conversation volume.
     *
     * @return array<int, array{dow: int, hour: int, count: int}>
     */
    public function heatmap(Carbon $from, Carbon $to): array
    {
        return $this->remember('heatmap', $from, $to, function () use ($from, $to): array {
            return DB::connection('helpdesk')
                ->table('helpdesk_conversations')
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('WEEKDAY(created_at) + 1 as dow, HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('dow', 'hour')
                ->get()
                ->map(fn ($r): array => ['dow' => (int) $r->dow, 'hour' => (int) $r->hour, 'count' => (int) $r->count])
                ->all();
        });
    }

    /**
     * Health-score bands for customers active in the range (batched, no N+1).
     *
     * @return array{healthy: int, neutral: int, at_risk: int, total: int}
     */
    public function customerSegments(Carbon $from, Carbon $to): array
    {
        return $this->remember('customers', $from, $to, function () use ($from, $to): array {
            $customerIds = Conversation::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('customer_id')
                ->distinct()
                ->limit(500)
                ->pluck('customer_id')
                ->all();

            $scores = $this->healthScores->scoresFor($customerIds);

            $healthy = $neutral = $atRisk = 0;
            foreach ($scores as $score) {
                match (true) {
                    $score >= 70 => $healthy++,
                    $score >= 40 => $neutral++,
                    default => $atRisk++,
                };
            }

            return [
                'healthy' => $healthy,
                'neutral' => $neutral,
                'at_risk' => $atRisk,
                'total' => count($scores),
            ];
        });
    }

    private function remember(string $key, Carbon $from, Carbon $to, \Closure $callback): array
    {
        $cacheKey = sprintf('helpdeskanalytics:%s:%s:%s', $key, $from->timestamp, $to->timestamp);

        return Cache::remember($cacheKey, self::CACHE_TTL, $callback);
    }
}

<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Carbon;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowExecution;

/**
 * Analytics blocks for the flow dashboard (summary, CSAT, AI resolution,
 * drop-off per node and A/B comparison). Extracted verbatim from
 * ChatFlowAnalyticsController so the controller only orchestrates range + cache.
 */
class ChatFlowAnalyticsService
{
    /**
     * Status breakdown + resolution rate for a flow's sessions within the window.
     *
     * @return array{total: int, completed: int, transferred: int, abandoned: int, failed: int, active: int, resolution_rate: float}
     */
    public function buildSummary(ChatFlow $chatFlow, ?Carbon $from = null): array
    {
        $byStatus = $chatFlow->sessions()
            ->when($from, fn ($q) => $q->where('started_at', '>=', $from))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalSessions = (int) $byStatus->sum();
        $completed = (int) ($byStatus['completed'] ?? 0);
        $transferred = (int) ($byStatus['transferred'] ?? 0);

        return [
            'total' => $totalSessions,
            'completed' => $completed,
            'transferred' => $transferred,
            'abandoned' => (int) ($byStatus['abandoned'] ?? 0),
            'failed' => (int) ($byStatus['failed'] ?? 0),
            'active' => (int) ($byStatus['active'] ?? 0),
            'resolution_rate' => $totalSessions > 0
                ? round(($completed + $transferred) / $totalSessions * 100, 1)
                : 0.0,
        ];
    }

    /**
     * A/B comparison: when the flow has a configured variant (`ab_variant_id`),
     * compute the same headline metrics for both arms so resolution, CSAT and
     * abandonment can be read side by side. Returns null when no variant is
     * configured (or it no longer exists), so the section stays hidden.
     *
     * The data model carries a single variant id per flow, so this compares the
     * binary A (this flow) vs B (the variant); true multivariant would need a
     * variant list on `trigger_conditions` first.
     *
     * @param  array{summary: array<string, mixed>, csat: array<string, mixed>, ai: array<string, mixed>}  $self
     * @return array{split: int, variants: array<int, array{key: string, flow: ChatFlow, summary: array<string, mixed>, csat: array<string, mixed>, ai: array<string, mixed>}>}|null
     */
    public function buildAbComparison(ChatFlow $chatFlow, ?Carbon $from, array $self): ?array
    {
        $variantId = $chatFlow->trigger_conditions['ab_variant_id'] ?? null;

        if (! $variantId) {
            return null;
        }

        $variant = ChatFlow::query()->whereKey($variantId)->first();

        if (! $variant || $variant->id === $chatFlow->id) {
            return null;
        }

        return [
            'split' => (int) ($chatFlow->trigger_conditions['ab_split'] ?? 50),
            'variants' => [
                [
                    'key' => 'A',
                    'flow' => $chatFlow,
                    'summary' => $self['summary'],
                    'csat' => $self['csat'],
                    'ai' => $self['ai'],
                ],
                [
                    'key' => 'B',
                    'flow' => $variant,
                    'summary' => $this->buildSummary($variant, $from),
                    'csat' => $this->buildCsatMetrics($variant, $from),
                    'ai' => $this->buildAiMetrics($variant, $from),
                ],
            ],
        ];
    }

    /**
     * Resolution metrics for the AI node: of the sessions that hit an `ai_response`
     * node, how many the bot resolved vs. escalated to an agent.
     *
     * @return array{used: int, resolved: int, escalated: int, rate: float}
     */
    public function buildAiMetrics(ChatFlow $chatFlow, ?Carbon $from = null): array
    {
        $empty = ['used' => 0, 'resolved' => 0, 'escalated' => 0, 'rate' => 0.0];
        $sessionIds = $chatFlow->sessions()
            ->when($from, fn ($q) => $q->where('started_at', '>=', $from))
            ->pluck('id');

        if ($sessionIds->isEmpty()) {
            return $empty;
        }

        $aiSessionIds = ChatFlowExecution::query()
            ->whereIn('session_id', $sessionIds)
            ->where('node_type', 'ai_response')
            ->distinct()
            ->pluck('session_id');

        if ($aiSessionIds->isEmpty()) {
            return $empty;
        }

        $statusCounts = $chatFlow->sessions()
            ->whereIn('id', $aiSessionIds)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $resolved = (int) ($statusCounts['completed'] ?? 0);
        $escalated = (int) ($statusCounts['transferred'] ?? 0);
        $used = $aiSessionIds->count();

        return [
            'used' => $used,
            'resolved' => $resolved,
            'escalated' => $escalated,
            'rate' => $used > 0 ? round($resolved / $used * 100, 1) : 0.0,
        ];
    }

    /**
     * CSAT metrics: of the sessions that answered a satisfaction survey, the
     * average score and the share of satisfied customers. The scale (1-5, 1-10,
     * thumbs) is read from the flow's first csat node so the threshold matches.
     *
     * @return array{answered: int, average: float, satisfied: int, rate: float, max: int}
     */
    public function buildCsatMetrics(ChatFlow $chatFlow, ?Carbon $from = null): array
    {
        $empty = ['answered' => 0, 'average' => 0.0, 'satisfied' => 0, 'rate' => 0.0, 'max' => 5];

        [$max, $threshold, $thumbs] = $this->resolveCsatScale($chatFlow);

        // `csat_score_value` is a VIRTUAL column over context->csat_score, covered
        // by the (chat_flow_id, started_at, csat_score_value) index, so the JSON
        // blob is never read. The score may be stored as a free-text answer when
        // the customer didn't reply with a number, so the numeric guard stays in PHP.
        $scores = $chatFlow->sessions()
            ->when($from, fn ($q) => $q->where('started_at', '>=', $from))
            ->whereNotNull('csat_score_value')
            ->pluck('csat_score_value')
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0);

        if ($scores->isEmpty()) {
            return $empty;
        }

        $satisfied = $thumbs
            ? $scores->filter(fn ($v) => $v === 1)->count()
            : $scores->filter(fn ($v) => $v >= $threshold)->count();

        return [
            'answered' => $scores->count(),
            'average' => round($scores->avg(), 1),
            'satisfied' => $satisfied,
            'rate' => round($satisfied / $scores->count() * 100, 1),
            'max' => $max,
        ];
    }

    /**
     * Weekly CSAT trend: for every ISO week with at least one numeric answer,
     * the number of responses, average score and satisfaction rate — same
     * scale/threshold logic as buildCsatMetrics(). Bucketed in PHP over the
     * rows selected from the (chat_flow_id, started_at, csat_score_value)
     * index, since MariaDB week-bucketing in SQL would need to run per driver.
     *
     * @return array<int, array{week: string, answered: int, average: float, rate: float}>
     */
    public function buildCsatTrend(ChatFlow $chatFlow, ?Carbon $from = null): array
    {
        [, $threshold, $thumbs] = $this->resolveCsatScale($chatFlow);

        $scores = $chatFlow->sessions()
            ->when($from, fn ($q) => $q->where('started_at', '>=', $from))
            ->whereNotNull('csat_score_value')
            ->get(['started_at', 'csat_score_value'])
            ->filter(fn ($session) => is_numeric($session->csat_score_value))
            ->map(fn ($session) => [
                'week' => $session->started_at->copy()->startOfWeek()->toDateString(),
                'value' => (int) $session->csat_score_value,
            ])
            ->filter(fn ($row) => $row['value'] > 0);

        return $scores
            ->groupBy('week')
            ->map(function ($rows, $week) use ($threshold, $thumbs) {
                $values = $rows->pluck('value');
                $satisfied = $thumbs
                    ? $values->filter(fn ($v) => $v === 1)->count()
                    : $values->filter(fn ($v) => $v >= $threshold)->count();

                return [
                    'week' => $week,
                    'answered' => $values->count(),
                    'average' => round($values->avg(), 1),
                    'rate' => round($satisfied / $values->count() * 100, 1),
                ];
            })
            ->sortKeys()
            ->values()
            ->all();
    }

    /**
     * [max, satisfied threshold, is-thumbs-scale] for the flow's first `csat`
     * node, so buildCsatMetrics() and buildCsatTrend() agree on what counts as
     * a satisfied answer.
     *
     * @return array{0: int, 1: int, 2: bool}
     */
    private function resolveCsatScale(ChatFlow $chatFlow): array
    {
        $csatNode = collect($chatFlow->nodes ?? [])->firstWhere('type', 'csat');
        $scale = $csatNode['data']['scale'] ?? '1-5';

        return match ($scale) {
            'thumbs' => [2, 1, true],
            '1-10' => [10, 8, false],
            default => [5, 4, false],
        };
    }

    /**
     * Drop-off per node: how many sessions reached each node, and how many got
     * stuck/abandoned there (their last node) without completing the flow.
     *
     * @return array<int, array{node_id: string, label: string, type: string, reached: int, dropped: int, rate: float}>
     */
    public function buildDropOff(ChatFlow $chatFlow, ?Carbon $from = null): array
    {
        $sessionsInWindow = fn () => $chatFlow->sessions()
            ->when($from, fn ($q) => $q->where('started_at', '>=', $from));

        if (! $sessionsInWindow()->exists()) {
            return [];
        }

        // How many distinct sessions executed each node.
        $reached = ChatFlowExecution::query()
            ->whereIn('session_id', $sessionsInWindow()->select('id'))
            ->selectRaw('node_id, count(distinct session_id) as total')
            ->groupBy('node_id')
            ->pluck('total', 'node_id');

        // Sessions that ended without completing — where did they stop?
        $dropped = $sessionsInWindow()
            ->whereIn('status', ['abandoned', 'failed'])
            ->whereNotNull('current_node_id')
            ->selectRaw('current_node_id, count(*) as total')
            ->groupBy('current_node_id')
            ->pluck('total', 'current_node_id');

        $nodes = collect($chatFlow->nodes ?? [])
            ->filter(fn ($n) => ($n['type'] ?? '') !== 'branchItem');

        return $nodes->map(function ($node) use ($reached, $dropped) {
            $reachedCount = (int) ($reached[$node['id']] ?? 0);
            $droppedCount = (int) ($dropped[$node['id']] ?? 0);

            return [
                'node_id' => $node['id'],
                'label' => $node['label'] ?? $node['type'],
                'type' => $node['type'],
                'reached' => $reachedCount,
                'dropped' => $droppedCount,
                'rate' => $reachedCount > 0 ? round($droppedCount / $reachedCount * 100, 1) : 0.0,
            ];
        })
            ->filter(fn ($row) => $row['reached'] > 0 || $row['dropped'] > 0)
            ->sortByDesc('dropped')
            ->values()
            ->all();
    }

    /**
     * Per-node reliability/performance: for each node run by this flow's
     * sessions in the window, executions, average/max duration and failure
     * rate. Sorted slowest-first so hotspots surface immediately.
     *
     * @return array<int, array{node_id: string, label: string, node_type: string, executions: int, avg_ms: float, max_ms: int, failures: int, failure_rate: float}>
     */
    public function buildNodeLatency(ChatFlow $chatFlow, ?Carbon $from = null): array
    {
        $sessionsInWindow = $chatFlow->sessions()
            ->when($from, fn ($q) => $q->where('started_at', '>=', $from))
            ->select('id');

        $rows = ChatFlowExecution::query()
            ->whereIn('session_id', $sessionsInWindow)
            ->selectRaw(
                'node_id, node_type, count(*) as executions, avg(duration_ms) as avg_ms, '.
                'max(duration_ms) as max_ms, sum(case when status = ? then 1 else 0 end) as failures',
                ['failed']
            )
            ->groupBy('node_id', 'node_type')
            ->get();

        $nodes = collect($chatFlow->nodes ?? [])->keyBy('id');

        return $rows
            ->map(function ($row) use ($nodes) {
                $executions = (int) $row->executions;
                $failures = (int) $row->failures;

                return [
                    'node_id' => $row->node_id,
                    'label' => $nodes[$row->node_id]['label'] ?? $row->node_type,
                    'node_type' => $row->node_type,
                    'executions' => $executions,
                    'avg_ms' => round((float) $row->avg_ms, 1),
                    'max_ms' => (int) $row->max_ms,
                    'failures' => $failures,
                    'failure_rate' => $executions > 0 ? round($failures / $executions * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('avg_ms')
            ->values()
            ->all();
    }

    /**
     * Flow-level alert: `http_request` nodes whose failure rate breaches the
     * configured threshold, with enough executions to be meaningful, so a
     * single flaky webhook surfaces above the raw per-node latency table.
     *
     * @param  array<int, array{node_id: string, label: string, node_type: string, executions: int, avg_ms: float, max_ms: int, failures: int, failure_rate: float}>  $nodeLatency
     * @return array<int, array{node_id: string, label: string, executions: int, failures: int, failure_rate: float}>
     */
    public function buildHttpFailureAlerts(array $nodeLatency): array
    {
        $minExecutions = (int) config('helpdeskchatflow.analytics.http_failure_alert_min', 5);
        $thresholdRate = (float) config('helpdeskchatflow.analytics.http_failure_alert_rate', 0.2) * 100;

        return collect($nodeLatency)
            ->filter(fn ($node) => $node['node_type'] === 'http_request')
            ->filter(fn ($node) => $node['executions'] >= $minExecutions)
            ->filter(fn ($node) => $node['failure_rate'] >= $thresholdRate)
            ->map(fn ($node) => [
                'node_id' => $node['node_id'],
                'label' => $node['label'],
                'executions' => $node['executions'],
                'failures' => $node['failures'],
                'failure_rate' => $node['failure_rate'],
            ])
            ->values()
            ->all();
    }
}

<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\HelpdeskSocial\Models\SocialAgentWorkload;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialCompetitor;
use Modules\HelpdeskSocial\Models\SocialMention;
use Modules\HelpdeskSocial\Models\SocialMetrics;
use Modules\HelpdeskSocial\Models\SocialSlaPolicy;

class SocialAnalyticsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.analytics.view'), 403);

        $since = now()->parse($request->get('since', now()->subDays(30)->toDateString()));
        $until = now()->parse($request->get('until', now()->toDateString()));

        $period = CarbonPeriod::create($since, '1 day', $until);
        $dates = collect($period)->map(fn ($d) => $d->format('Y-m-d'));

        $byDate = SocialComment::query()
            ->whereBetween('posted_at', [$since->startOfDay(), $until->endOfDay()])
            ->selectRaw('DATE(posted_at) as date, COUNT(*) as comments, COUNT(replied_at) as replies, AVG(TIMESTAMPDIFF(SECOND, posted_at, replied_at)) as avg_response_time, SUM(status = "escalated") as escalations')
            ->groupByRaw('DATE(posted_at)')
            ->get()
            ->keyBy('date');

        $byPlatform = SocialComment::query()
            ->whereBetween('posted_at', [$since->startOfDay(), $until->endOfDay()])
            ->selectRaw('platform, COUNT(*) as count, COUNT(replied_at) as replies, SUM(CASE WHEN auto_replied = 1 THEN 1 ELSE 0 END) as auto_replied')
            ->groupBy('platform')
            ->get()
            ->map(fn ($item) => [
                'count' => (int) $item->count,
                'replies' => (int) $item->replies,
                'auto_replied' => (int) $item->auto_replied,
            ])
            ->sortByDesc('count')
            ->values();

        $byIntent = SocialComment::query()
            ->whereBetween('posted_at', [$since->startOfDay(), $until->endOfDay()])
            ->whereNotNull('intent')
            ->selectRaw('intent, COUNT(*) as count, AVG(CASE WHEN urgency = "critical" THEN 4 WHEN urgency = "high" THEN 3 WHEN urgency = "medium" THEN 2 ELSE 1 END) as avg_urgency')
            ->groupBy('intent')
            ->get()
            ->map(fn ($item) => [
                'count' => (int) $item->count,
                'avg_urgency' => $item->avg_urgency !== null ? round((float) $item->avg_urgency, 1) : null,
            ])
            ->sortByDesc('count')
            ->values();

        $metrics = SocialMetrics::query()
            ->whereBetween('date', [$since->toDateString(), $until->toDateString()])
            ->get()
            ->keyBy(fn ($m) => $m->date->format('Y-m-d'));

        $summary = SocialComment::query()
            ->whereBetween('posted_at', [$since->startOfDay(), $until->endOfDay()])
            ->selectRaw('COUNT(*) as total_comments, COUNT(replied_at) as total_replies, AVG(TIMESTAMPDIFF(SECOND, posted_at, replied_at)) as avg_response_time, SUM(CASE WHEN auto_replied = 1 THEN 1 ELSE 0 END) as auto_replied')
            ->first();

        return response()->json([
            'period' => ['since' => $since->toDateString(), 'until' => $until->toDateString()],
            'summary' => [
                'total_comments' => (int) ($summary->total_comments ?? 0),
                'total_replies' => (int) ($summary->total_replies ?? 0),
                'avg_response_time_min' => $summary?->avg_response_time ? round((float) $summary->avg_response_time / 60, 1) : null,
                'auto_reply_rate' => ($summary->total_comments ?? 0) > 0
                    ? round((int) $summary->auto_replied / (int) $summary->total_comments * 100, 1)
                    : 0,
            ],
            'by_date' => $dates->mapWithKeys(function ($date) use ($byDate, $metrics) {
                $dayStats = $byDate->get($date);

                return [$date => [
                    'comments' => (int) ($dayStats?->comments ?? 0),
                    'replies' => (int) ($dayStats?->replies ?? 0),
                    'avg_response_time_min' => $dayStats?->avg_response_time ? round((float) $dayStats->avg_response_time / 60, 1) : null,
                    'escalations' => (int) ($dayStats?->escalations ?? 0),
                    'metrics_automation_rate' => $metrics->get($date)?->automation_rate,
                ]];
            }),
            'by_platform' => $byPlatform,
            'by_intent' => $byIntent,
        ]);
    }

    public function metrics(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.analytics.view'), 403);

        $since = now()->parse($request->get('since', now()->subDays(30)->toDateString()));
        $until = now()->parse($request->get('until', now()->toDateString()));

        $metrics = SocialMetrics::query()
            ->whereBetween('date', [$since->toDateString(), $until->toDateString()])
            ->orderBy('date')
            ->get();

        return response()->json([
            'period' => ['since' => $since->toDateString(), 'until' => $until->toDateString()],
            'metrics' => $metrics->map(fn ($m) => [
                'date' => $m->date->format('Y-m-d'),
                'total_comments' => $m->comments_received,
                'total_replies' => $m->replies_sent,
                'avg_response_time_min' => $m->avg_response_time_seconds ? round($m->avg_response_time_seconds / 60, 1) : null,
                'auto_reply_rate' => $m->automation_rate !== null ? round($m->automation_rate * 100, 1) : null,
                'escalation_rate' => $m->comments_received > 0
                    ? round($m->escalated_count / $m->comments_received * 100, 1)
                    : 0,
                'avg_sentiment' => $this->sentimentScore($m->sentiment_breakdown),
            ]),
        ]);
    }

    public function agentsPerformance(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.analytics.view'), 403);

        $since = now()->parse($request->get('since', now()->subDays(30)->toDateString()));
        $until = now()->parse($request->get('until', now()->toDateString()));

        $assignedComments = SocialComment::query()
            ->whereNotNull('assigned_to_user_id')
            ->whereBetween('replied_at', [$since->startOfDay(), $until->endOfDay()])
            ->with('assignedTo')
            ->get()
            ->groupBy('assigned_to_user_id');

        return response()->json([
            'period' => ['since' => $since->toDateString(), 'until' => $until->toDateString()],
            'agents' => $assignedComments->map(function ($items) {
                $first = $items->first();

                return [
                    'user_id' => $first->assigned_to_user_id,
                    'user_name' => $first->assignedTo?->name ?? 'Desconocido',
                    'comments_assigned' => $items->count(),
                    'comments_replied' => $items->whereNotNull('replied_at')->count(),
                    'avg_response_time_min' => $this->avgResponseTime($items),
                ];
            })->sortByDesc('comments_replied')->values(),
        ]);
    }

    public function agentPerformance(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.analytics.view'), 403);

        $since = now()->parse($request->get('since', now()->subDays(30)->toDateString()));
        $until = now()->parse($request->get('until', now()->toDateString()));

        $assignedComments = SocialComment::query()
            ->whereNotNull('assigned_to_user_id')
            ->whereBetween('replied_at', [$since->startOfDay(), $until->endOfDay()])
            ->with('assignedTo')
            ->get()
            ->groupBy('assigned_to_user_id');

        $workloads = SocialAgentWorkload::with('user')->get()->keyBy('user_id');

        return response()->json([
            'period' => ['since' => $since->toDateString(), 'until' => $until->toDateString()],
            'agents' => $assignedComments->map(function ($items) use ($workloads) {
                $first = $items->first();
                $workload = $workloads->get($first->assigned_to_user_id);

                return [
                    'user_id' => $first->assigned_to_user_id,
                    'user_name' => $first->assignedTo?->name ?? 'Desconocido',
                    'comments_assigned' => $items->count(),
                    'comments_replied' => $items->whereNotNull('replied_at')->count(),
                    'avg_response_time_min' => $this->avgResponseTime($items),
                    'active_workload' => $workload?->active_assigned_count ?? 0,
                ];
            })->sortByDesc('comments_replied')->values(),
        ]);
    }

    public function slaOverview(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.analytics.view'), 403);

        $since = now()->parse($request->get('since', now()->subDays(30)->toDateString()));
        $until = now()->parse($request->get('until', now()->toDateString()));

        $comments = SocialComment::query()
            ->whereBetween('posted_at', [$since->startOfDay(), $until->endOfDay()])
            ->whereNotNull('replied_at')
            ->get();

        $avgResponse = $this->avgResponseTime($comments);
        $total = $comments->count();
        $slaPolicies = SocialSlaPolicy::active()->get();
        $defaultTarget = $slaPolicies->first()?->response_time_minutes ?? 60;

        return response()->json([
            'period' => ['since' => $since->toDateString(), 'until' => $until->toDateString()],
            'total_comments_with_reply' => $total,
            'avg_response_time_min' => $avgResponse,
            'default_sla_target_min' => $defaultTarget,
            'sla_compliance_rate' => $total > 0 && $avgResponse !== null
                ? round($comments->filter(fn ($c) => $c->posted_at->diffInMinutes($c->replied_at) <= $defaultTarget)->count() / $total * 100, 1)
                : null,
            'policies' => $slaPolicies->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'response_time_minutes' => $p->response_time_minutes,
                'resolution_time_minutes' => $p->resolution_time_minutes,
            ]),
        ]);
    }

    public function sentimentBreakdown(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.analytics.view'), 403);

        $since = now()->parse($request->get('since', now()->subDays(30)->toDateString()));
        $until = now()->parse($request->get('until', now()->toDateString()));

        $mentions = SocialMention::query()
            ->whereBetween('discovered_at', [$since->startOfDay(), $until->endOfDay()])
            ->get();

        $bySentiment = $mentions->groupBy('sentiment');
        $total = $mentions->count();

        return response()->json([
            'period' => ['since' => $since->toDateString(), 'until' => $until->toDateString()],
            'total_mentions' => $total,
            'breakdown' => [
                'positive' => $bySentiment->get('positive', collect())->count(),
                'neutral' => $bySentiment->get('neutral', collect())->count(),
                'negative' => $bySentiment->get('negative', collect())->count(),
            ],
            'average_score' => $total > 0 ? round($mentions->avg('sentiment_score'), 3) : null,
            'by_platform' => $mentions->groupBy('platform')->map(fn ($items) => [
                'total' => $items->count(),
                'positive' => $items->where('sentiment', 'positive')->count(),
                'neutral' => $items->where('sentiment', 'neutral')->count(),
                'negative' => $items->where('sentiment', 'negative')->count(),
            ]),
        ]);
    }

    public function competitorComparison(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.analytics.view'), 403);

        $competitors = SocialCompetitor::with('latestMetrics')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'competitors' => $competitors->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'platform' => $c->platform,
                'metrics' => $c->latestMetrics->map(fn ($m) => [
                    'type' => $m->metric_type,
                    'value' => $m->value,
                    'captured_at' => $m->captured_at?->toIso8601String(),
                ]),
            ]),
        ]);
    }

    /**
     * @param  Collection<int, SocialComment>  $comments
     */
    private function avgResponseTime(Collection $comments): ?float
    {
        $times = $comments->filter(fn ($c) => $c->posted_at && $c->replied_at)
            ->map(fn ($c) => $c->posted_at->diffInMinutes($c->replied_at));

        return $times->count() > 0 ? round($times->avg(), 1) : null;
    }

    private function sentimentScore(?array $breakdown): ?float
    {
        if (! $breakdown) {
            return null;
        }

        $positive = $breakdown['positive'] ?? 0;
        $neutral = $breakdown['neutral'] ?? 0;
        $negative = $breakdown['negative'] ?? 0;
        $total = $positive + $neutral + $negative;

        if ($total === 0) {
            return null;
        }

        return round((($positive - $negative) / $total) * 100, 1);
    }
}

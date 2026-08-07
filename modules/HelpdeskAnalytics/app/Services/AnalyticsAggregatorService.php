<?php

namespace Modules\HelpdeskAnalytics\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\CsatRating;

/**
 * Read-only cross-channel analytics aggregator for the Helpdesk inbox
 * (web/email/whatsapp/facebook/instagram conversations). Every method uses
 * grouped/aggregate SQL (no N+1) and is cached briefly. Consolidates the metrics
 * previously scattered across the CSAT/Trends/Heatmap/AgentPerformance reports.
 *
 * Aislamiento por bandeja: cada método acepta un User opcional. Un usuario sin
 * helpdesk.manage solo agrega sobre las bandejas que tiene asignadas via
 * AgentInboxCapacity (mismo mecanismo que Conversation::scopeForAgent /
 * Customer::scopeForAgent, fail-closed sin bandejas). Managers y llamadas sin
 * usuario (CLI/sistema) ven todo.
 */
class AnalyticsAggregatorService
{
    private const CACHE_TTL = 300;

    /**
     * Kept as a plain string literal (never a top-of-file import, never ::class)
     * so it is only ever resolved behind the ticketsAvailable() guard and this
     * module never breaks when HelpdeskTickets is disabled.
     */
    private const TICKET = 'Modules\\HelpdeskTickets\\Models\\Ticket';

    public function __construct(
        private readonly HealthScoreBatchService $healthScores,
    ) {}

    /**
     * @return array{conversations: int, closed: int, open: int, avg_first_response_seconds: int, csat_avg: float}
     */
    public function overview(Carbon $from, Carbon $to, ?User $user = null): array
    {
        $inboxIds = $this->accessibleInboxIds($user);

        return $this->remember('overview', $from, $to, $inboxIds, function () use ($from, $to, $inboxIds): array {
            $row = DB::connection('helpdesk')
                ->table('helpdesk_conversations')
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to])
                ->when($inboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $inboxIds))
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
                ->when($inboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $inboxIds))
                ->count();

            $csatAvg = CsatRating::query()
                ->whereBetween('answered_at', [$from, $to])
                ->whereNotNull('answered_at')
                ->when($inboxIds !== null, fn ($q) => $q->whereHas(
                    'conversation',
                    fn ($c) => $c->whereIn('inbox_id', $inboxIds)
                ))
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
    public function channelDistribution(Carbon $from, Carbon $to, ?User $user = null): array
    {
        $inboxIds = $this->accessibleInboxIds($user);

        return $this->remember('channels', $from, $to, $inboxIds, function () use ($from, $to, $inboxIds): array {
            return DB::connection('helpdesk')
                ->table('helpdesk_conversations')
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to])
                ->when($inboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $inboxIds))
                ->selectRaw('COALESCE(channel, \'web\') as channel, COUNT(*) as count')
                ->groupBy('channel')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($r): array => ['channel' => (string) $r->channel, 'count' => (int) $r->count])
                ->all();
        });
    }

    /**
     * Con usuario restringido solo aparecen agentes con actividad en sus
     * bandejas (las conversaciones/mensajes/CSAT se filtran por inbox).
     *
     * Cruza conversaciones (bandeja) y tickets (sin bandeja) por agente en un
     * único ranking — antes eran comparativas separadas (AgentPerformanceController
     * y este mismo método solo veían conversaciones; TicketReportsService::topAgents
     * solo veía tickets cerrados, sin tiempos). Los tickets no tienen inbox_id, así
     * que su lado del cruce sigue el mismo fail-closed que ticketMetrics(): solo se
     * suman con usuario sin restricción de bandeja (managers).
     *
     * @return array<int, array{name: string, closed_count: int, csat_avg: float, avg_response_seconds: int, message_count: int, ticket_closed_count: int, ticket_avg_first_response_minutes: int, ticket_avg_resolution_minutes: int, total_closed: int}>
     */
    public function agentPerformance(Carbon $from, Carbon $to, ?User $user = null): array
    {
        $inboxIds = $this->accessibleInboxIds($user);

        return $this->remember('agents', $from, $to, $inboxIds, function () use ($from, $to, $inboxIds): array {
            $conversationRows = DB::connection('helpdesk')
                ->table('helpdesk_conversations as c')
                ->whereBetween('c.closed_at', [$from, $to])
                ->whereNotNull('c.assignee_id')
                ->when($inboxIds !== null, fn ($q) => $q->whereIn('c.inbox_id', $inboxIds))
                ->selectRaw('c.assignee_id, COUNT(*) as closed_count, AVG(CASE WHEN c.first_response_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, c.created_at, c.first_response_at) END) as avg_response_sec')
                ->groupBy('c.assignee_id')
                ->get()
                ->keyBy('assignee_id');

            $ticketRows = collect();
            if ($this->ticketsAvailable() && $inboxIds === null) {
                /** @var class-string<Model> $ticketClass */
                $ticketClass = self::TICKET;

                $ticketRows = $ticketClass::query()
                    ->whereBetween('closed_at', [$from, $to])
                    ->whereNotNull('assignee_id')
                    ->selectRaw('
                        assignee_id,
                        COUNT(*) as ticket_closed_count,
                        AVG(CASE WHEN first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at) END) as ticket_avg_first_response,
                        AVG(TIMESTAMPDIFF(MINUTE, created_at, closed_at)) as ticket_avg_resolution
                    ')
                    ->groupBy('assignee_id')
                    ->get()
                    ->keyBy('assignee_id');
            }

            $agentIds = $conversationRows->keys()->merge($ticketRows->keys())->unique()->values();

            if ($agentIds->isEmpty()) {
                return [];
            }

            $csatByAgent = CsatRating::query()
                ->whereIn('agent_id', $agentIds)
                ->whereBetween('answered_at', [$from, $to])
                ->whereNotNull('answered_at')
                ->when($inboxIds !== null, fn ($q) => $q->whereHas(
                    'conversation',
                    fn ($c) => $c->whereIn('inbox_id', $inboxIds)
                ))
                ->groupBy('agent_id')
                ->selectRaw('agent_id, AVG(rating) as csat_avg')
                ->pluck('csat_avg', 'agent_id');

            $messagesByAgent = DB::connection('helpdesk')
                ->table('helpdesk_conversation_items')
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('user_id')
                ->where('type', 'message')
                ->when($inboxIds !== null, fn ($q) => $q->whereIn('conversation_id', function ($sub) use ($inboxIds) {
                    $sub->select('id')
                        ->from('helpdesk_conversations')
                        ->whereIn('inbox_id', $inboxIds);
                }))
                ->groupBy('user_id')
                ->selectRaw('user_id, COUNT(*) as msg_count')
                ->pluck('msg_count', 'user_id');

            $users = User::whereIn('id', $agentIds)->get(['id', 'firstname', 'lastname'])->keyBy('id');

            return $agentIds->map(function ($agentId) use ($conversationRows, $ticketRows, $csatByAgent, $messagesByAgent, $users): array {
                $convRow = $conversationRows->get($agentId);
                $ticketRow = $ticketRows->get($agentId);
                $closedCount = (int) ($convRow->closed_count ?? 0);
                $ticketClosedCount = (int) ($ticketRow->ticket_closed_count ?? 0);

                return [
                    'name' => ($u = $users->get($agentId))
                        ? (trim("{$u->firstname} {$u->lastname}") ?: "Agente #{$agentId}")
                        : "Agente #{$agentId}",
                    'closed_count' => $closedCount,
                    'csat_avg' => round((float) ($csatByAgent[$agentId] ?? 0), 2),
                    'avg_response_seconds' => (int) round((float) ($convRow->avg_response_sec ?? 0)),
                    'message_count' => (int) ($messagesByAgent[$agentId] ?? 0),
                    'ticket_closed_count' => $ticketClosedCount,
                    'ticket_avg_first_response_minutes' => (int) round((float) ($ticketRow->ticket_avg_first_response ?? 0)),
                    'ticket_avg_resolution_minutes' => (int) round((float) ($ticketRow->ticket_avg_resolution ?? 0)),
                    'total_closed' => $closedCount + $ticketClosedCount,
                ];
            })->sortByDesc('total_closed')->values()->all();
        });
    }

    /**
     * @return array<int, array{date: string, created: int, closed: int}>
     */
    public function trends(Carbon $from, Carbon $to, ?User $user = null): array
    {
        $inboxIds = $this->accessibleInboxIds($user);

        return $this->remember('trends', $from, $to, $inboxIds, function () use ($from, $to, $inboxIds): array {
            $created = DB::connection('helpdesk')
                ->table('helpdesk_conversations')
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to])
                ->when($inboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $inboxIds))
                ->selectRaw('DATE(created_at) as d, COUNT(*) as cnt')
                ->groupBy('d')
                ->pluck('cnt', 'd');

            $closed = DB::connection('helpdesk')
                ->table('helpdesk_conversations')
                ->whereNull('deleted_at')
                ->whereBetween('closed_at', [$from, $to])
                ->when($inboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $inboxIds))
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
    public function heatmap(Carbon $from, Carbon $to, ?User $user = null): array
    {
        $inboxIds = $this->accessibleInboxIds($user);

        return $this->remember('heatmap', $from, $to, $inboxIds, function () use ($from, $to, $inboxIds): array {
            return DB::connection('helpdesk')
                ->table('helpdesk_conversations')
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to])
                ->when($inboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $inboxIds))
                ->selectRaw('WEEKDAY(created_at) + 1 as dow, HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('dow', 'hour')
                ->get()
                ->map(fn ($r): array => ['dow' => (int) $r->dow, 'hour' => (int) $r->hour, 'count' => (int) $r->count])
                ->all();
        });
    }

    /**
     * Maximum number of distinct customers scored in one segment computation.
     * Beyond this the result is a sample (flagged via 'sampled') so the dashboard
     * never presents a partial count as if it were the whole population.
     */
    private const SEGMENT_CUSTOMER_CAP = 5000;

    /**
     * Health-score bands for customers active in the range (batched, no N+1).
     *
     * Scores every distinct customer in the range up to SEGMENT_CUSTOMER_CAP, in
     * chunks, so the counts are exact for realistic ranges. The previous version
     * silently capped at 500 and reported those counts as the total. When the cap
     * is exceeded the result is flagged 'sampled' => true instead of lying.
     *
     * @return array{healthy: int, neutral: int, at_risk: int, total: int, sampled: bool}
     */
    public function customerSegments(Carbon $from, Carbon $to, ?User $user = null): array
    {
        $inboxIds = $this->accessibleInboxIds($user);

        return $this->remember('customers', $from, $to, $inboxIds, function () use ($from, $to, $inboxIds): array {
            // Fetch one past the cap to detect (and flag) truncation.
            $customerIds = Conversation::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('customer_id')
                ->when($inboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $inboxIds))
                ->distinct()
                ->limit(self::SEGMENT_CUSTOMER_CAP + 1)
                ->pluck('customer_id')
                ->all();

            $sampled = count($customerIds) > self::SEGMENT_CUSTOMER_CAP;
            if ($sampled) {
                $customerIds = array_slice($customerIds, 0, self::SEGMENT_CUSTOMER_CAP);
            }

            $healthy = $neutral = $atRisk = $total = 0;

            // Score in chunks so a large range never builds one giant IN() list.
            foreach (array_chunk($customerIds, 500) as $chunk) {
                foreach ($this->healthScores->scoresFor($chunk) as $score) {
                    match (true) {
                        $score >= 70 => $healthy++,
                        $score >= 40 => $neutral++,
                        default => $atRisk++,
                    };
                    $total++;
                }
            }

            return [
                'healthy' => $healthy,
                'neutral' => $neutral,
                'at_risk' => $atRisk,
                'total' => $total,
                'sampled' => $sampled,
            ];
        });
    }

    /**
     * Ticket metrics for the omnichannel dashboard, mirroring the criteria used
     * by HelpdeskReportsController (HelpdeskTickets' own reporting) so both
     * panels agree on the same numbers. Gated by helpdesk_tickets_enabled():
     * returns all-zero metrics instead of querying when tickets are disabled.
     *
     * @return array{total_created: int, total_closed: int, total_resolved: int, sla_breached: int, unassigned: int, avg_first_response_minutes: int, avg_resolution_minutes: int, by_priority: array<int, array{priority: string, count: int}>}
     */
    public function ticketMetrics(Carbon $from, Carbon $to, ?User $user = null): array
    {
        if (! $this->ticketsAvailable()) {
            return $this->emptyTicketMetrics();
        }

        // Los tickets no tienen bandeja (inbox_id), así que no pueden acotarse
        // al aislamiento por bandeja del agente. Fail-closed: un usuario
        // restringido no ve métricas globales de tickets; los managers sí.
        if ($this->accessibleInboxIds($user) !== null) {
            return $this->emptyTicketMetrics();
        }

        return $this->remember('tickets', $from, $to, null, function () use ($from, $to): array {
            /** @var class-string<Model> $ticketClass */
            $ticketClass = self::TICKET;

            $row = $ticketClass::query()
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('
                    COUNT(*) as total_created,
                    SUM(CASE WHEN closed_at IS NOT NULL THEN 1 ELSE 0 END) as total_closed,
                    SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) as total_resolved,
                    SUM(CASE WHEN sla_resolution_breached = 1 THEN 1 ELSE 0 END) as sla_breached,
                    SUM(CASE WHEN assignee_id IS NULL THEN 1 ELSE 0 END) as unassigned,
                    AVG(CASE WHEN first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at) END) as avg_first_response,
                    AVG(CASE WHEN closed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, closed_at) END) as avg_resolution
                ')
                ->first();

            $byPriority = $ticketClass::query()
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($r): array => ['priority' => (string) $r->priority, 'count' => (int) $r->count])
                ->all();

            return [
                'total_created' => (int) ($row->total_created ?? 0),
                'total_closed' => (int) ($row->total_closed ?? 0),
                'total_resolved' => (int) ($row->total_resolved ?? 0),
                'sla_breached' => (int) ($row->sla_breached ?? 0),
                'unassigned' => (int) ($row->unassigned ?? 0),
                'avg_first_response_minutes' => (int) round((float) ($row->avg_first_response ?? 0)),
                'avg_resolution_minutes' => (int) round((float) ($row->avg_resolution ?? 0)),
                'by_priority' => $byPriority,
            ];
        });
    }

    /**
     * @return array{total_created: int, total_closed: int, total_resolved: int, sla_breached: int, unassigned: int, avg_first_response_minutes: int, avg_resolution_minutes: int, by_priority: array<int, array{priority: string, count: int}>}
     */
    private function emptyTicketMetrics(): array
    {
        return [
            'total_created' => 0,
            'total_closed' => 0,
            'total_resolved' => 0,
            'sla_breached' => 0,
            'unassigned' => 0,
            'avg_first_response_minutes' => 0,
            'avg_resolution_minutes' => 0,
            'by_priority' => [],
        ];
    }

    private function ticketsAvailable(): bool
    {
        return function_exists('helpdesk_tickets_enabled')
            && helpdesk_tickets_enabled()
            && class_exists(self::TICKET);
    }

    /**
     * Inbox ids que el usuario puede ver, o null cuando no hay restricción
     * (sin usuario — llamadas de CLI/sistema — o manager con helpdesk.manage).
     * Mismo mecanismo de aislamiento por bandeja que Conversation::scopeForAgent
     * (AgentInboxCapacity): un agente restringido sin bandejas asignadas no ve
     * nada (fail-closed).
     *
     * @return array<int, int>|null
     */
    private function accessibleInboxIds(?User $user): ?array
    {
        if ($user === null || $user->hasPermissionTo('helpdesk.manage')) {
            return null;
        }

        return AgentInboxCapacity::query()
            ->where('user_id', $user->id)
            ->pluck('inbox_id')
            ->map(fn ($id) => (int) $id)
            ->sort() // orden estable → clave de caché estable por conjunto de bandejas
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>|null  $inboxIds  null = sin restricción por bandeja
     */
    private function remember(string $key, Carbon $from, Carbon $to, ?array $inboxIds, \Closure $callback): array
    {
        // El scope forma parte de la clave: los agregados restringidos por
        // bandeja jamás deben compartir caché con la vista global (ni entre
        // agentes con bandejas distintas).
        $scope = $inboxIds === null ? 'all' : md5(implode(',', $inboxIds));

        $cacheKey = sprintf('helpdeskanalytics:%s:%s:%s:%s', $key, $scope, $from->timestamp, $to->timestamp);

        return Cache::remember($cacheKey, self::CACHE_TTL, $callback);
    }
}

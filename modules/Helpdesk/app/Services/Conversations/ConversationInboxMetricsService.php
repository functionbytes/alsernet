<?php

namespace Modules\Helpdesk\Services\Conversations;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Services\AgentPresenceService;

/**
 * Datos agregados y cacheados que alimentan el sidebar/statusbar del inbox
 * (ConversationsController::index()). Extraido para que index() se enfoque
 * en construir la query filtrada de conversaciones, no en calcular metricas.
 */
class ConversationInboxMetricsService
{
    public function __construct(
        private readonly AgentPresenceService $presence,
    ) {}

    /**
     * @return array{active_channels: int, agents_online: int, sla_avg_seconds: int, resolved_today: int}
     */
    public function statusbarMetrics(): array
    {
        return cache()->remember(
            'helpdesk:inbox:statusbar',
            30,
            function () {
                $today = now()->startOfDay();

                $activeChannels = (int) Conversation::query()
                    ->whereNotNull('channel')
                    ->where('channel', '!=', '')
                    ->distinct()
                    ->count('channel');

                $resolvedToday = (int) Conversation::query()
                    ->where('closed_at', '>=', $today)
                    ->count();

                $firstResponseAvg = (int) Conversation::query()
                    ->whereNotNull('first_response_at')
                    ->whereDate('first_response_at', $today)
                    ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) as avg_sec')
                    ->value('avg_sec');

                // The SQL `sessions` table is never populated — SESSION_DRIVER is
                // redis, so real sessions live there, not in this table. This
                // always counted 0 agents online regardless of who was actually
                // using the panel. AgentPresenceService already tracks real
                // presence via a Redis heartbeat (updated by the widget's
                // /presence/heartbeat poll) — reuse that instead.
                $agentsOnline = count($this->presence->getOnlineAgents());

                return [
                    'active_channels' => $activeChannels,
                    'agents_online' => $agentsOnline,
                    'sla_avg_seconds' => $firstResponseAvg,
                    'resolved_today' => $resolvedToday,
                ];
            }
        );
    }

    /**
     * @param  array<int>|null  $userInboxIds
     * @return array<string, int>
     */
    public function sidebarCounters(?int $userId, ?array $userInboxIds): array
    {
        // Cache::flexible (SWR de Laravel 12) evita el stampede: sirve el
        // valor "stale" mientras recalcula en background. Por usuario para
        // aislar el conteo "mine" entre agentes.
        return Cache::flexible(
            'helpdesk:inbox:counters:'.($userId ?? 'guest'),
            [45, 120],
            function () use ($userId, $userInboxIds) {
                $base = Conversation::query()
                    ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds));

                // Inbox counters exclude conversations the bot is still handling.
                $inbox = (clone $base)->withoutActiveBot();

                // Un único GROUP BY para los 5 contadores por canal (antes 5 COUNT).
                $channelCounts = (clone $inbox)
                    ->whereNotNull('channel')
                    ->where('channel', '!=', '')
                    ->selectRaw('channel, COUNT(*) as cnt')
                    ->groupBy('channel')
                    ->pluck('cnt', 'channel');

                // unassigned/archived/spam comparten la misma base ($inbox, sin
                // join) y solo difieren en una columna simple: un único SELECT con
                // SUM(CASE WHEN ...) sobre helpdesk_conversations directamente
                // (antes 3 COUNT idénticos salvo el where). pending/closed/blocked/vip
                // se quedan en sus propias queries porque necesitan whereHas (join a
                // status/customer), igual que defaultViewVisible() (unread/mine/urgent).
                $columnCounts = (clone $inbox)->selectRaw(
                    'SUM(CASE WHEN assignee_id IS NULL THEN 1 ELSE 0 END) as unassigned,
                    SUM(CASE WHEN is_archived = 1 THEN 1 ELSE 0 END) as archived,
                    SUM(CASE WHEN is_spam = 1 THEN 1 ELSE 0 END) as spam'
                )->first();

                return [
                    // Sin leer: Conversation::scopeUnreadFor() — única fuente de
                    // verdad, compartida con listJson() (ver comentario del scope).
                    'unread' => $userId
                        ? (clone $inbox)->unreadFor($userId)->count()
                        : 0,
                    'mine' => $userId ? (clone $inbox)->defaultViewVisible()->where('assignee_id', $userId)->count() : 0,
                    'unassigned' => (int) $columnCounts->unassigned,
                    'urgent' => (clone $inbox)->defaultViewVisible()->where('priority', 'urgent')->count(),
                    'pending' => (clone $inbox)
                        ->whereHas('status', fn ($q) => $q->where('name', 'Esperando'))
                        ->count(),
                    'archived' => (int) $columnCounts->archived,
                    // "Cerradas" in the sidebar means resolved/closed status
                    // (is_open=false) — NOT archived, a separate concept. The
                    // link used to point at ?archived=1 and show this same
                    // 'archived' count, so it always read 0 even with closed
                    // conversations sitting right there.
                    'closed' => (clone $inbox)
                        ->whereHas('status', fn ($q) => $q->where('is_open', false))
                        ->count(),
                    'blocked' => (clone $inbox)
                        ->whereHas('customer', fn ($c) => $c->whereNotNull('banned_at'))
                        ->count(),
                    'spam' => (int) $columnCounts->spam,
                    'whatsapp' => (int) ($channelCounts['whatsapp'] ?? 0),
                    'facebook' => (int) ($channelCounts['facebook'] ?? 0),
                    'instagram' => (int) ($channelCounts['instagram'] ?? 0),
                    'email' => (int) ($channelCounts['email'] ?? 0),
                    'web' => (int) ($channelCounts['web'] ?? 0),
                    'vip' => (clone $inbox)
                        ->whereHas('customer', fn ($c) => $c->where('total_conversations', '>=', 5))
                        ->count(),
                    // Conversaciones que el bot está atendiendo (supervisión).
                    'bot' => (clone $base)->handledByBot()->count(),
                    // Papelera: conversaciones eliminadas (soft-deleted).
                    'deleted' => (clone $base)->onlyTrashed()->count(),
                ];
            }
        );
    }

    /**
     * Forget the cached sidebar/list counters for a user so a read/unread
     * change is reflected on the very next request instead of waiting out
     * the cache TTL (up to 120s for sidebarCounters, 30s for listJson).
     * Call this right after marking a conversation as read.
     */
    public function forgetCountersFor(?int $userId): void
    {
        Cache::forget('helpdesk:inbox:counters:'.($userId ?? 'guest'));
        Cache::forget('helpdesk:inbox:list-counters:'.($userId ?? 'guest'));
    }

    /**
     * Per-inbox sidebar entries filtrados por los inboxes asignados al
     * agente. Managers (helpdesk.manage) ven todos.
     *
     * @param  array<int>|null  $userInboxIds
     * @return Collection<int, Inbox>
     */
    public function sidebarInboxes(?int $userId, ?array $userInboxIds): Collection
    {
        $cacheKey = $userInboxIds === null
            ? 'helpdesk:inbox:sidebar-list:all'
            : 'helpdesk:inbox:sidebar-list:user:'.$userId;

        return cache()->remember(
            $cacheKey,
            60,
            function () use ($userInboxIds) {
                $inboxList = Inbox::query()
                    ->where('is_active', true)
                    ->when($userInboxIds !== null, fn ($q) => $q->whereIn('id', $userInboxIds))
                    ->orderBy('name')
                    ->get(['id', 'name', 'channel_type', 'color', 'icon']);

                // Un único GROUP BY para todos los contadores por inbox (antes 1
                // COUNT por inbox — N+1 con N inboxes activos). Filtrado igual que
                // la vista "Inbox" por defecto (is_open/is_archived/sin bot activo)
                // para que el número mostrado en el sidebar coincida con lo que el
                // agente realmente ve al abrir ese inbox — antes contaba TODO
                // (incluidas conversaciones resueltas/archivadas), mostrando un
                // número mayor a cero con la lista vacía debajo.
                $counts = Conversation::query()
                    ->whereIn('inbox_id', $inboxList->pluck('id'))
                    ->whereHas('status', fn ($q) => $q->where('is_open', true))
                    ->where('is_archived', false)
                    ->withoutActiveBot()
                    ->selectRaw('inbox_id, COUNT(*) as cnt')
                    ->groupBy('inbox_id')
                    ->pluck('cnt', 'inbox_id');

                return $inboxList->each(
                    fn (Inbox $inbox) => $inbox->setAttribute('conversations_count', (int) ($counts[$inbox->id] ?? 0))
                );
            }
        );
    }

    /**
     * Contador de conversaciones por equipo para la sección "EQUIPOS" del
     * sidebar — mismo criterio y mismo patrón de GROUP BY único que
     * sidebarInboxes(), que hasta ahora era el único que lo aplicaba
     * (Group::orderBy('name')->get() en index() nunca traía el conteo).
     *
     * @return Collection<int, Group>
     */
    public function sidebarGroups(): Collection
    {
        return cache()->remember(
            'helpdesk:inbox:sidebar-groups',
            60,
            function () {
                $groups = Group::orderBy('name')->get();

                $counts = Conversation::query()
                    ->whereIn('group_id', $groups->pluck('id'))
                    ->whereHas('status', fn ($q) => $q->where('is_open', true))
                    ->where('is_archived', false)
                    ->withoutActiveBot()
                    ->selectRaw('group_id, COUNT(*) as cnt')
                    ->groupBy('group_id')
                    ->pluck('cnt', 'group_id');

                return $groups->each(
                    fn (Group $group) => $group->setAttribute('conversations_count', (int) ($counts[$group->id] ?? 0))
                );
            }
        );
    }

    /**
     * @return Collection<int, ConversationTag>
     */
    public function inboxTags(): Collection
    {
        return cache()->remember(
            'helpdesk:inbox:tags',
            60,
            fn () => ConversationTag::query()
                ->where('is_active', true)
                ->withCount('conversations')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Agentes activos ordenados por carga de trabajo (menos conversaciones
     * abiertas primero), para el selector de "asignar a".
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function agentWorkload(): \Illuminate\Support\Collection
    {
        // Conteo de conversaciones abiertas por agente: un único GROUP BY
        // cacheado (antes era una subconsulta correlacionada por fila de users).
        $openCounts = Cache::remember(
            'helpdesk:inbox:agent-open-counts',
            30,
            fn (): array => Conversation::query()
                ->whereNull('closed_at')
                ->whereNotNull('assignee_id')
                ->selectRaw('assignee_id, COUNT(*) as cnt')
                ->groupBy('assignee_id')
                ->pluck('cnt', 'assignee_id')
                ->all()
        );

        // Bug real encontrado en QA visual (ago-2026): sin el filtro por rol,
        // esta query devolvía TODOS los usuarios del sistema (~1000, la
        // mayoría fixtures de otros módulos) — el selector "Agente" del
        // inbox era una lista casi infinita en vez de los agentes reales del
        // equipo. Mismo rol que ya usa HelpdeskTickets\Services\
        // AssignmentService::getAvailableAgents()/CatalogCacheService::agents().
        return User::query()
            ->leftJoin('helpdesk_agent_settings', 'helpdesk_agent_settings.user_id', '=', 'users.id')
            ->select([
                'users.id', 'users.firstname', 'users.lastname', 'users.email', 'users.role',
                'helpdesk_agent_settings.presence_state as helpdesk_status',
            ])
            // Sin whereNull('users.deleted_at'): esa columna no existe en la
            // tabla users (la crearia 2026_04_19_110003_add_soft_deletes_to_users_table
            // del modulo Auth, que esta sin ejecutar), asi que el inbox entero
            // moria con "Unknown column 'users.deleted_at' in 'WHERE'".
            //
            // Tampoco hace falta ponerla a mano: App\Models\User no usa el
            // trait SoftDeletes, de modo que nadie marcaria un usuario como
            // borrado y el filtro seria siempre cierto. Y si algun dia User lo
            // adopta, Eloquent aplicara el scope global por su cuenta — un
            // whereNull manual solo volveria a romperse cuando el esquema y el
            // codigo se separen.
            ->whereHas('roles', fn ($q) => $q->where('name', 'helpdesk-agent'))
            ->get()
            ->each(fn (User $agent) => $agent->setAttribute('open_count', (int) ($openCounts[$agent->id] ?? 0)))
            ->sortBy([['open_count', 'asc'], ['firstname', 'asc']])
            ->values();
    }
}

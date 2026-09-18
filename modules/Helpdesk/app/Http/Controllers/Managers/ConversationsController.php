<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Events\ConversationMarkedAsSpam;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\ConversationStatusChanged;
use Modules\Helpdesk\Events\ConversationTagAdded;
use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Events\InboxItemChanged;
use Modules\Helpdesk\Filters\ConversationFilter;
use Modules\Helpdesk\Http\Requests\ConversationAjaxActionRequest;
use Modules\Helpdesk\Http\Requests\Managers\LinkConversationCustomerRequest;
use Modules\Helpdesk\Http\Requests\MarkSpamRequest;
use Modules\Helpdesk\Http\Requests\SendHsmRequest;
use Modules\Helpdesk\Http\Requests\SnoozeConversationRequest;
use Modules\Helpdesk\Http\Requests\StoreConversationMessageRequest;
use Modules\Helpdesk\Http\Requests\StoreConversationRequest;
use Modules\Helpdesk\Http\Requests\StoreScheduledMessageRequest;
use Modules\Helpdesk\Http\Requests\UpdateConversationRequest;
use Modules\Helpdesk\Jobs\SendScheduledMessageJob;
use Modules\Helpdesk\Jobs\SyncCustomerCommerceJob;
use Modules\Helpdesk\Jobs\UnsnoozeConversationJob;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Campaigns\WhatsAppTemplate;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationView;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Notifications\ConversationAssignedNotification;
use Modules\Helpdesk\Services\ConversationMessageService;
use Modules\Helpdesk\Services\Conversations\ActivityMessageService;
use Modules\Helpdesk\Services\Conversations\ConversationInboxMetricsService;
use Modules\Helpdesk\Services\ConversationTagService;
use Modules\Helpdesk\Services\CsatService;
use Modules\Helpdesk\Services\HsmConversationService;
use Modules\Helpdesk\Services\OutboundMessageService;
use Modules\HelpdeskDocument\Services\ConversationDocumentLinker;
use Modules\HelpdeskLivechat\Models\WidgetSession;

class ConversationsController extends Controller
{
    public function __construct(
        private ConversationTagService $tagService,
        private ConversationInboxMetricsService $inboxMetrics,
        private ActivityMessageService $activityMessages,
    ) {
        $this->middleware('can:helpdesk.conversations.view')->only(['index', 'show', 'pane', 'listJson', 'kanban']);
        $this->middleware('can:helpdesk.conversations.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.conversations.update')->only([
            'edit', 'update', 'close', 'reopen', 'archive', 'unarchive',
            'storeMessage', 'snooze', 'togglePin', 'toggleMute',
            'sendHsm', 'merge', 'mergeCandidates',
            'saveDraft', 'storeScheduledMessage', 'createTicket',
        ]);
        $this->middleware('can:helpdesk.conversations.delete')->only(['destroy', 'restore', 'forceDelete', 'blockContact']);
        $this->middleware('can:helpdesk.conversations.update')->only(['markSpam', 'sendCsatSurvey']);
    }

    /**
     * Returns inbox IDs the current user may access, or null if unrestricted.
     * Users with helpdesk.manage see all inboxes; regular agents only see
     * the inboxes assigned via helpdesk_agent_inbox_capacity.
     *
     * A restricted agent with NO assigned inboxes returns an empty array (sees
     * nothing) — never null — so the listing does not leak conversations from
     * other inboxes.
     *
     * @return int[]|null null = no restriction (helpdesk.manage); array = allowed inbox IDs
     */
    private function getUserInboxIds(): ?array
    {
        $user = auth()->user();

        if ($user->can('helpdesk.manage')) {
            return null;
        }

        return AgentInboxCapacity::where('user_id', $user->id)->pluck('inbox_id')->all();
    }

    /**
     * Ensure the current agent may act on a conversation item: managers see
     * everything; other agents only items in their assigned inboxes. Prevents
     * cross-inbox access via a message id (reactToMessage/forwardMessage/messageInfo).
     */
    private function assertItemAccess(ConversationItem $item): void
    {
        $inboxIds = $this->getUserInboxIds();

        if ($inboxIds === null) {
            return; // helpdesk.manage → full access
        }

        $item->loadMissing('conversation');

        abort_unless(
            $item->conversation && in_array($item->conversation->inbox_id, $inboxIds, true),
            403
        );
    }

    /**
     * Display a listing of conversations
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Conversation::class);

        $userId = auth()->id();
        $userInboxIds = $this->getUserInboxIds();

        [$views, $currentView] = $this->resolveCurrentView($request, $userId);

        $query = $this->buildFilteredConversationsQuery($request, $userInboxIds, $currentView);

        $conversations = $query->paginate(50)->appends($request->query());
        $statuses = ConversationStatus::active()->ordered()->get();
        $groups = $this->inboxMetrics->sidebarGroups();

        // Conversation::scopeDefaultViewVisible() — debe coincidir con lo que
        // realmente se ve al aterrizar en el inbox sin filtros (vista "Todas
        // las abiertas"), no con el total absoluto incluyendo cerradas/archivadas.
        $totalConversations = Conversation::query()
            ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds))
            ->withoutActiveBot()
            ->defaultViewVisible()
            ->count();

        // Metricas/contadores agregados y cacheados del sidebar+statusbar —
        // ver ConversationInboxMetricsService (extraido de aqui: index()
        // mezclaba filtrado de query con 5 bloques de cache independientes).
        $inboxTags = $this->inboxMetrics->inboxTags();
        $statusbarMetrics = $this->inboxMetrics->statusbarMetrics();
        $sidebarCounters = $this->inboxMetrics->sidebarCounters($userId, $userInboxIds);
        $inboxes = $this->inboxMetrics->sidebarInboxes($userInboxIds);

        // Sin ?selected= explícito no se auto-selecciona la primera
        // conversación: se deja el estado vacío "elige un chat" ya diseñado
        // en thread.blade.php/right-panel.blade.php en vez de abrir una al azar.
        $selectedId = $request->integer('selected') ?: null;

        // Carga del hilo seleccionado + borrador del composer. Compartido con el
        // endpoint pane() para que el cambio de conversación vía SPA renderice
        // exactamente lo mismo que una carga de página completa.
        $pane = $this->buildConversationPaneData($selectedId, $userInboxIds);
        $selectedConversation = $pane['selectedConversation'];

        $inboxGroups = $this->groupConversationsForInbox($conversations->getCollection(), $selectedId);

        $composerDraft = $pane['composerDraft'];
        $agents = $this->inboxMetrics->agentWorkload();

        return view('helpdesk::helpdesk.inbox.index', [
            'conversations' => $conversations,
            'inboxGroups' => $inboxGroups,
            'statuses' => $statuses,
            'groups' => $groups,
            'agents' => $agents,
            'views' => $views,
            'currentView' => $currentView,
            'filters' => $request->only(['status', 'assignee', 'group', 'priority', 'search', 'archived', 'channel', 'unread', 'mine', 'urgent', 'vip', 'tag']),
            'totalConversations' => $totalConversations,
            'sidebarCounters' => $sidebarCounters,
            'sidebarInboxes' => $inboxes,
            'inboxTags' => $inboxTags,
            'statusbarMetrics' => $statusbarMetrics,
            'selectedConversationId' => $selectedId,
            'selectedConversation' => $selectedConversation,
            'composerDraft' => $composerDraft,
        ]);
    }

    /**
     * Build the data that the thread + right-panel partials need for a selected
     * conversation. Shared by index() (full page) and pane() (SPA swap) so both
     * render identically.
     *
     * Only the latest ~50 thread items are eager-loaded (older items paginate via
     * olderItems()); customer.externalIds is eager-loaded so the right panel does
     * not trigger a lazy load. Items are re-sorted ascending because the view
     * renders them oldest-first.
     *
     * @param  int[]|null  $userInboxIds  null = unrestricted (helpdesk.manage)
     * @return array{selectedConversation: ?Conversation, composerDraft: mixed}
     */
    private function buildConversationPaneData(?int $selectedId, ?array $userInboxIds): array
    {
        $selectedConversation = $selectedId
            ? Conversation::query()
                ->with([
                    'customer.externalIds', 'status', 'assignee', 'conversationTags',
                    // items() defines orderBy('created_at', 'asc'); reorder() it out first
                    // or MySQL keeps that ASC clause and limit(50) grabs the *oldest* 50
                    // instead of the newest (see Conversation::getLatestMessage()).
                    'items' => fn ($q) => $q->reorder()->latest('id')->limit(50),
                    // Evita el N+1 de $item->user/$item->author al renderizar el hilo
                    // (thread.blade.php accede a ambos por cada uno de los 50 ítems).
                    'items.user:id,firstname,lastname',
                    'items.author:id,name',
                ])
                ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds))
                ->find($selectedId)
            : null;

        if ($selectedConversation) {
            $selectedConversation->setRelation(
                'items',
                $selectedConversation->items->sortBy('id')->values()
            );
        }

        $composerDraft = $selectedConversation?->drafts()
            ->where('user_id', auth()->id())
            ->first();

        if ($selectedConversation) {
            $this->dispatchConversationOpenedSideEffects($selectedConversation);
        }

        return [
            'selectedConversation' => $selectedConversation,
            'composerDraft' => $composerDraft,
        ];
    }

    /**
     * Best-effort side effects fired once per pane render (index()/pane() both
     * funnel through buildConversationPaneData()) — moved out of
     * right-panel.blade.php (QUAL-03), which used to run all three inline on
     * every render of a Blade partial: e-commerce sync, document auto-link,
     * and the widget session→conversation cache mapping. None of these feed
     * the view's output, so a failure here must never break the pane render.
     */
    private function dispatchConversationOpenedSideEffects(Conversation $conversation): void
    {
        $customer = $conversation->customer;

        // Auto-deteccion y guardado del vinculo de e-commerce (PrestaShop + gestion).
        // Se saca del camino critico del render: en lugar de llamar a la API externa
        // sincronamente en cada repintado, se despacha un job en cola protegido por un
        // guard de cache para que el sync real corra ~1 vez/hora por cliente.
        if ($customer?->email && Cache::add('hd:commerce-sync:'.$customer->id, true, 3600)) {
            SyncCustomerCommerceJob::dispatch($customer);
        }

        if (helpdesk_document_enabled() && class_exists(ConversationDocumentLinker::class)) {
            try {
                $linker = app(ConversationDocumentLinker::class);
                $documents = $linker->documentsForConversation($conversation);

                if ($documents->isNotEmpty()) {
                    // Crea el vínculo si falta, lo re-apunta si quedó roto y
                    // refresca el snapshot informativo si el estado cambió.
                    $linker->syncLink($conversation, $documents);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (class_exists(WidgetSession::class)) {
            $metadata = is_array($conversation->metadata)
                ? $conversation->metadata
                : (json_decode((string) ($conversation->metadata ?? '{}'), true) ?? []);
            $sessionToken = $metadata['widget_session_token'] ?? null;

            if ($sessionToken) {
                // Cache session→conversation mapping so heartbeat broadcasts know the conversation_id.
                Cache::put('helpdesklivechat:session_conv:'.$sessionToken, $conversation->id, now()->addDay());
            }
        }
    }

    /**
     * Render the thread + right-panel HTML for a single conversation. Consumed by
     * the inbox SPA navigation: the frontend swaps these two columns in place
     * (no full page reload) when the agent opens or switches conversations.
     *
     * Falls back gracefully on the client — if this request fails the inbox
     * reverts to a full navigation to ?selected={id}.
     */
    public function pane(Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $userInboxIds = $this->getUserInboxIds();

        abort_if(
            $userInboxIds !== null && ! in_array($conversation->inbox_id, $userInboxIds, true),
            403
        );

        $pane = $this->buildConversationPaneData($conversation->id, $userInboxIds);

        return view('helpdesk::helpdesk.inbox.partials.pane', [
            'selectedConversation' => $pane['selectedConversation'],
            'selectedConversationId' => $conversation->id,
            'composerDraft' => $pane['composerDraft'],
        ]);
    }

    /**
     * Return a JSON payload with rendered HTML for the inbox list + counts.
     * Used by the frontend AJAX refresh (SPA-style navigation without full reload).
     */
    /**
     * Keep chatbot-handled conversations out of the inbox by default. The "bot"
     * view (?bot=1) lists them for supervision; a search bypasses the filter so
     * they remain findable in history.
     */
    private function applyBotVisibility(Builder $query, Request $request): void
    {
        if ($request->boolean('bot')) {
            $query->handledByBot();

            return;
        }

        // La papelera muestra TODO lo eliminado, incluidas las que estaba
        // atendiendo el bot; no aplicar el filtro withoutActiveBot ahí.
        if ($request->input('view') === 'deleted') {
            return;
        }

        if (! $request->filled('search')) {
            $query->withoutActiveBot();
        }
    }

    /**
     * Resolve the ConversationView the inbox filters should apply: the one
     * requested via ?viewId=, or the user's default/first view. The trash
     * (?view=deleted) never falls back to a default view — it must show
     * everything without inheriting is_open-style filters.
     *
     * @return array{0: Collection<int, ConversationView>, 1: ?ConversationView}
     */
    private function resolveCurrentView(Request $request, int $userId): array
    {
        $views = ConversationView::forUser($userId)->ordered()->get();

        $currentView = null;
        if ($request->has('viewId')) {
            $currentView = $views->firstWhere('id', $request->viewId);
        }

        if (! $currentView && $request->input('view') !== 'deleted') {
            $currentView = $views->firstWhere('is_default', true) ?? $views->first();
        }

        return [$views, $currentView];
    }

    /**
     * Build the filtered + sorted conversations query shared by index() (full
     * page) and listJson() (AJAX refresh): eager loads, inbox restriction, bot
     * visibility, saved view filters, quick-filter chips and sort order.
     *
     * @param  int[]|null  $userInboxIds  null = unrestricted (helpdesk.manage)
     */
    private function buildFilteredConversationsQuery(Request $request, ?array $userInboxIds, ?ConversationView $currentView): Builder
    {
        $userId = auth()->id();
        $filter = new ConversationFilter($request);

        $query = Conversation::query()
            ->with([
                'customer', 'status', 'assignee', 'inbox', 'lastMessage',
                'reads' => fn ($q) => $q->where('user_id', auth()->id()),
            ])
            ->withCount(['items as incoming_messages_count' => fn ($q) => $q->where('type', 'message')->whereNull('user_id')])
            ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds))
            ->when($request->input('view') === 'deleted', fn ($q) => $q->onlyTrashed());

        // Conversations the chatbot is handling stay out of the inbox until it
        // hands off to an agent. The "bot" view shows them (supervision) and a
        // search still finds them (history).
        $this->applyBotVisibility($query, $request);

        if ($currentView && ! empty($currentView->filters)) {
            $filter->applyViewFilters($query, $currentView->filters);
        }

        $filter->apply($query);

        // Quick-filter chips (unread / mine / urgent / vip)
        $query
            ->when(
                $request->boolean('unread'),
                fn ($q) => $q->whereDoesntHave(
                    'reads',
                    fn ($r) => $r->where('user_id', $userId)
                )
            )
            ->when(
                $request->boolean('mine'),
                fn ($q) => $q->where('assignee_id', $userId)
            )
            ->when(
                $request->boolean('urgent'),
                fn ($q) => $q->where('priority', 'urgent')
            )
            ->when(
                $request->boolean('vip'),
                fn ($q) => $q->whereHas(
                    'customer',
                    fn ($c) => $c->where('total_conversations', '>=', 5)
                )
            )
            ->when(
                $request->filled('tag'),
                // El modal "Filtrar conversaciones" puede mandar varios chips
                // de etiqueta a la vez ("3,4"): $request->integer('tag') solo
                // castea el primer número y descarta el resto en silencio.
                fn ($q) => $q->whereHas(
                    'conversationTags',
                    fn ($t) => $t->whereIn(
                        'helpdesk_conversation_tags.id',
                        array_map('intval', explode(',', (string) $request->input('tag')))
                    )
                )
            );

        $this->applySortOrder($query, $request->input('sort', 'newest'), $userId);

        return $query;
    }

    /**
     * Group a conversations collection into today/yesterday/week/older buckets
     * for the inbox list partial, converting each row via toInboxArray().
     *
     * @param  Collection<int, Conversation>  $conversations
     * @return Collection<string, array<int, array<string, mixed>>>
     */
    private function groupConversationsForInbox(Collection $conversations, ?int $selectedId): Collection
    {
        return $conversations
            ->groupBy(function (Conversation $c): string {
                $at = $c->last_message_at ?? $c->updated_at ?? $c->created_at;

                if (! $at) {
                    return 'older';
                }

                if ($at->isToday()) {
                    return 'today';
                }

                if ($at->isYesterday()) {
                    return 'yesterday';
                }

                return $at->isAfter(now()->subDays(7)) ? 'week' : 'older';
            })
            ->map(fn ($items) => $items->map(fn ($c) => $c->toInboxArray($selectedId))->values()->all());
    }

    public function listJson(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $userInboxIds = $this->getUserInboxIds();

        // $views is unused here (unlike index(), listJson() never returns it to
        // the client) but resolveCurrentView() is still called so the same
        // ConversationView query executes as before this refactor.
        [, $currentView] = $this->resolveCurrentView($request, $userId);

        $query = $this->buildFilteredConversationsQuery($request, $userInboxIds, $currentView);

        $conversations = $query->paginate(50)->appends($request->query());

        $selectedId = $request->integer('selected') ?: null;

        $inboxGroups = $this->groupConversationsForInbox($conversations->getCollection(), $selectedId);

        // Los contadores del poll dependen solo del inbox del usuario (no de los
        // filtros de la petición), así que se cachean por usuario con TTL corto
        // para no recalcular ~5 COUNT en cada poll. Clave namespaced distinta a
        // la del sidebar (helpdesk:inbox:counters) para no colisionar de shape.
        $cachedCounts = Cache::remember(
            'helpdesk:inbox:list-counters:'.($userId ?? 'guest'),
            30,
            function () use ($userInboxIds, $userId): array {
                $baseCount = Conversation::query()
                    ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds))
                    ->withoutActiveBot();

                // Conversation::scopeDefaultViewVisible()/scopeUnreadFor() — única
                // fuente de verdad, compartida con
                // ConversationInboxMetricsService::sidebarCounters(). Antes estos
                // conteos no exigían is_open/is_archived, así que los badges
                // (refrescados por este endpoint en cada evento en vivo) divergían
                // del filtro real (p.ej. "Todas" 41 con la lista mostrando 21,
                // "Urgentes" 5 con la lista mostrando 3, "Sin leer" 20 con la
                // lista vacía).
                return [
                    'base_total' => (int) (clone $baseCount)->defaultViewVisible()->count(),
                    'unread' => $userId
                        ? (int) (clone $baseCount)->unreadFor($userId)->count()
                        : 0,
                    'mine' => (int) (clone $baseCount)
                        ->defaultViewVisible()
                        ->where('assignee_id', $userId)
                        ->count(),
                    'urgent' => (int) (clone $baseCount)
                        ->defaultViewVisible()
                        ->where('priority', 'urgent')
                        ->count(),
                    'channels' => (clone $baseCount)
                        ->selectRaw('channel, COUNT(*) as cnt')
                        ->groupBy('channel')
                        ->pluck('cnt', 'channel')
                        ->toArray(),
                ];
            }
        );

        $html = view('helpdesk::helpdesk.inbox.partials.list', [
            'inboxGroups' => $inboxGroups,
            'totalConversations' => $cachedCounts['base_total'],
            'selectedConversationId' => $selectedId,
        ])->render();

        $counts = [
            // 'total' feeds the sidebar's "Todas" badge (data-counter="total")
            // via JS polling — it must be the GLOBAL count, not the current
            // request's filtered count. It used to be $conversations->total(),
            // so navigating into e.g. "Sin leer" (?unread=1) overwrote "Todas"
            // with however many conversations were unread, and it stayed wrong
            // until a full page reload recomputed $totalConversations correctly.
            'total' => $cachedCounts['base_total'],
            'unread' => $cachedCounts['unread'],
            'mine' => $cachedCounts['mine'],
            'urgent' => $cachedCounts['urgent'],
            'channels' => $cachedCounts['channels'],
        ];

        return response()->json([
            'success' => true,
            'html' => $html,
            'counts' => $counts,
            // BANDEJAS/EQUIPOS/ETIQUETAS del sidebar — mismos datos cacheados
            // que index(), servidos aquí también porque este es el endpoint
            // que el listener de Echo ya llama (debounced) en cada evento en
            // tiempo real que afecta a esos contadores (ver
            // ConversationInboxMetricsService::invalidateSidebarStructureCaches()).
            'sidebar' => $this->sidebarStructureCounts($userInboxIds),
        ]);
    }

    /**
     * @param  int[]|null  $userInboxIds
     * @return array{inboxes: array<int, array{id: int, count: int}>, groups: array<int, array{id: int, count: int}>, tags: array<int, array{id: int, count: int}>}
     */
    private function sidebarStructureCounts(?array $userInboxIds): array
    {
        return [
            'inboxes' => $this->inboxMetrics->sidebarInboxes($userInboxIds)
                ->map(fn (Inbox $inbox) => ['id' => $inbox->id, 'count' => $inbox->conversations_count])
                ->values()
                ->all(),
            'groups' => $this->inboxMetrics->sidebarGroups()
                ->map(fn (Group $group) => ['id' => $group->id, 'count' => $group->conversations_count])
                ->values()
                ->all(),
            'tags' => $this->inboxMetrics->inboxTags()
                ->map(fn ($tag) => ['id' => $tag->id, 'count' => $tag->conversations_count])
                ->values()
                ->all(),
        ];
    }

    /**
     * Show the form for creating a new conversation
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Conversation::class);

        $customer = null;
        if ($request->has('customer')) {
            $customer = Customer::findOrFail($request->customer);
        }

        $statuses = ConversationStatus::open()->ordered()->get();

        return view('helpdesk::helpdesk.conversations.create', [
            'customer' => $customer,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Store a newly created conversation
     */
    public function store(StoreConversationRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $customer = Customer::findOrFail($validated['customer_id']);
        $channel = $validated['channel'] ?? 'web';

        // Una conversacion nueva por WhatsApp nunca puede tener la ventana de
        // 24h abierta: last_customer_message_at es una columna propia de cada
        // conversacion (no del cliente), asi que en una fila recien creada
        // siempre es null — el mismo motivo por el que el wizard exige
        // plantilla HSM en vez de texto libre. Si igual llega first_message
        // (API directa sin pasar por el wizard) se rechaza aqui, antes de
        // crear nada, en vez de dejar una conversacion huerfana o un intento
        // de envio que Meta va a rechazar.
        if ($channel === 'whatsapp' && filled($validated['first_message'] ?? null)) {
            $error = [
                'success' => false,
                'message' => __('helpdesk::helpdesk.inbox.thread.wa_window_closed'),
                'wa_window_closed' => true,
            ];

            if ($request->wantsJson()) {
                return response()->json($error, 422);
            }

            return back()->withInput()->with('error', $error['message']);
        }

        // Si el cliente ya tiene una conversación abierta en ese canal se
        // continúa esa (la plantilla/primer mensaje va ahí) en vez de abrir
        // otra en paralelo: con dos abiertas la respuesta del cliente acababa
        // en una y la plantilla en la otra. Solo canales con remitente
        // externo (WhatsApp/Messenger/Instagram): son un único hilo por cliente.
        $externalSenderId = $this->resolveExternalSenderId($channel, $customer);
        $open = $externalSenderId === null ? null : Conversation::query()
            ->where('customer_id', $customer->id)
            ->where('channel', $channel)
            ->where(fn ($q) => $q->whereNull('status_id')
                ->orWhereHas('status', fn ($s) => $s->where('is_open', true)))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if ($open) {
            $this->authorize('view', $open);

            if ($request->boolean('assign_self') && $open->assignee_id === null) {
                $open->assignTo(auth()->id());
            }

            if (filled($validated['first_message'] ?? null)) {
                app(ConversationMessageService::class)->store($open, [
                    'body' => $validated['first_message'],
                    'is_internal' => false,
                ]);
            }

            $message = __('Ya había una conversación abierta con este cliente (#:id): se continúa en ella.', ['id' => $open->id]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'reused' => true,
                    'message' => $message,
                    'conversation' => [
                        'id' => $open->id,
                        'url' => route('manager.helpdesk.conversations.index', ['selected' => $open->id]),
                    ],
                ]);
            }

            return redirect()->route('manager.helpdesk.conversations.show', $open)->with('success', $message);
        }

        $conversation = Conversation::create([
            'customer_id' => $customer->id,
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
            'channel' => $channel,
            'external_sender_id' => $externalSenderId,
            // Sin esto la conversacion nacia con inbox_id NULL: ConversationPolicy
            // y AgentInboxCapacity la vuelven invisible para agentes restringidos
            // por bandeja (incluido el propio creador), y rompe el aislamiento que
            // sí aplica el flujo real de webhooks (InboundMessageIngestor). Mismo
            // cache de 30 min por canal, misma key, para no duplicar la consulta.
            'inbox_id' => Cache::remember(
                "helpdesk:inbox_id:{$channel}",
                now()->addMinutes(30),
                fn () => Inbox::query()->where('channel_type', $channel)->value('id'),
            ),
        ]);
        $conversation->status_id = $validated['status_id'] ?? ConversationStatus::getDefault()?->id;
        $conversation->save();

        $customer->incrementConversationCount();

        if ($request->boolean('assign_self')) {
            $conversation->assignTo(auth()->id());
        }

        if (filled($validated['first_message'] ?? null)) {
            app(ConversationMessageService::class)->store($conversation, [
                'body' => $validated['first_message'],
                'is_internal' => false,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_created'),
                'conversation' => [
                    'id' => $conversation->id,
                    'url' => route('manager.helpdesk.conversations.index', ['selected' => $conversation->id]),
                ],
            ], 201);
        }

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', __('helpdesk::helpdesk.messages.conversation_created'));
    }

    /**
     * Resuelve el identificador externo (wa_id / PSID / IGSID) al que se le
     * enviarán los mensajes salientes, según el canal elegido y los datos
     * sociales ya guardados en el contacto. Mismo criterio que usan los
     * webhooks entrantes (WhatsAppMessageProcessor, FacebookMessageProcessor,
     * InstagramMessageProcessor) para que ambos flujos casen en la misma
     * conversación.
     */
    private function resolveExternalSenderId(string $channel, Customer $customer): ?string
    {
        return match ($channel) {
            'whatsapp' => $customer->whatsapp_phone ?: $customer->phone,
            'facebook' => $customer->facebook_psid,
            'instagram' => $customer->instagram_id,
            default => null,
        };
    }

    public function previewJson(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load(['status', 'assignee']);

        $agentName = fn ($user) => $user->fullName() ?: 'Agente';

        $messages = $conversation->items()
            ->messages()
            ->with(['user:id,firstname,lastname', 'author:id,name'])
            ->orderBy('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'body' => mb_substr(strip_tags($item->html_body ?? $item->body ?? ''), 0, 600),
                'is_from_agent' => $item->isFromAgent(),
                'sender_name' => $item->isFromAgent() ? $agentName($item->user) : null,
                'created_at' => $item->created_at->translatedFormat('d M H:i'),
            ])
            ->values();

        $assigneeName = $conversation->assignee
            ? $conversation->assignee->fullName() ?: null
            : null;

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'subject' => $conversation->subject,
                'channel' => $conversation->channel,
                'status' => $conversation->status ? [
                    'name' => $conversation->status->name,
                    'is_open' => (bool) $conversation->status->is_open,
                ] : null,
                'assignee' => $assigneeName ? ['name' => $assigneeName] : null,
                'created_at' => $conversation->created_at->translatedFormat('d M Y'),
            ],
            'messages' => $messages,
            'open_url' => route('manager.helpdesk.conversations.index', ['selected' => $conversation->id]),
        ]);
    }

    /**
     * Send a WhatsApp HSM template to the customer.
     */
    public function sendHsm(SendHsmRequest $request, Conversation $conversation, HsmConversationService $hsmConversations): JsonResponse
    {
        $validated = $request->validated();

        $item = $hsmConversations->sendToConversation(
            $conversation,
            $validated['template_name'],
            $validated['variables'] ?? [],
            $validated['language'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Plantilla en cola de envío.',
            'item' => [
                'id' => $item->id,
                'body' => $item->body,
                'is_internal' => false,
                'created_at' => $item->created_at?->toIso8601String(),
                'time' => $item->created_at?->format('H:i'),
                'author' => auth()->user()?->name,
                'is_outgoing' => true,
                'wa_message_id' => null,
            ],
        ], 201);
    }

    /**
     * Legacy /conversations/{conversation} URL — redirects to the inbox with the
     * conversation pre-selected. The standalone "show" page was deprecated in
     * favor of the unified inbox layout (left list + thread + right panel).
     */
    public function show(Conversation $conversation, Request $request): RedirectResponse
    {
        $this->authorize('view', $conversation);

        return redirect()->route('manager.helpdesk.conversations.index', array_merge(
            ['selected' => $conversation->id],
            $request->only(['viewId', 'group', 'search'])
        ));
    }

    /**
     * Update the specified conversation
     */
    public function update(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $conversation);

        // AJAX path: lightweight partial updates (tag/priority/assignee) — handled by ConversationAjaxActionRequest
        if ($request->ajax() || $request->wantsJson()) {
            return $this->handleAjaxUpdate($conversation);
        }

        // Regular form submission — validate via UpdateConversationRequest
        $validated = app(UpdateConversationRequest::class)->validated();

        $conversation->update([
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
        ]);

        $conversation->status_id = $validated['status_id'];

        if (isset($validated['assignee_id'])) {
            if ($validated['assignee_id'] && $validated['assignee_id'] !== $conversation->assignee_id) {
                $conversation->assignTo($validated['assignee_id']);
            } elseif (! $validated['assignee_id']) {
                $conversation->assignee_id = null;
                $conversation->assigned_at = null;
            }
        }

        if (isset($validated['is_archived'])) {
            $conversation->is_archived = $validated['is_archived'];
        }

        $conversation->save();

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', __('helpdesk::helpdesk.messages.conversation_updated'));
    }

    /**
     * Tag mutations only touch the conversation_tag_pivot table, so they
     * never trigger ConversationObserver::updated() (no column on
     * `conversations` changes) — the sidebar ETIQUETAS counters would stay
     * stale until the cache TTL expires. Bust the cache and reuse
     * ConversationUpdated as the "something changed, refresh what you need"
     * ping already broadcast on the inbox channel the sidebar listens to.
     */
    private function notifySidebarStructureChanged(Conversation $conversation): void
    {
        $this->inboxMetrics->invalidateSidebarStructureCaches();
        ConversationUpdated::dispatch($conversation, auth()->id());
    }

    /**
     * Handle AJAX partial updates (tag toggle, priority, assignee).
     */
    private function handleAjaxUpdate(Conversation $conversation): JsonResponse
    {
        $request = app(ConversationAjaxActionRequest::class);
        $action = $request->input('action');

        if ($action === 'add_tag') {
            $tag = $this->tagService->addTag($conversation, (int) $request->validated()['tag_id']);

            $this->notifySidebarStructureChanged($conversation);

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.tag_added'),
                'tag' => $tag,
            ]);
        }

        if ($action === 'remove_tag') {
            $this->tagService->removeTag($conversation, (int) $request->validated()['tag_id']);

            $this->notifySidebarStructureChanged($conversation);

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.tag_removed'),
            ]);
        }

        if ($request->has('tag_ids')) {
            $tagIds = array_map('intval', $request->validated()['tag_ids'] ?? []);

            $existingIds = $conversation->conversationTags()->pluck('tag_id')->map('intval')->all();
            $newIds = array_diff($tagIds, $existingIds);

            $conversation->conversationTags()->sync($tagIds);
            $conversation->load('conversationTags');

            foreach ($conversation->conversationTags->whereIn('id', $newIds) as $tag) {
                ConversationTagAdded::dispatch($conversation, $tag, auth()->id());
            }

            $this->notifySidebarStructureChanged($conversation);

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.tags_updated'),
            ]);
        }

        if ($request->has('priority')) {
            $conversation->update(['priority' => $request->validated()['priority']]);

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.priority_updated'),
            ]);
        }

        if ($request->has('status_id')) {
            $conversation->status_id = $request->validated()['status_id'];
            $statusChanged = $conversation->isDirty('status_id');
            $conversation->save();

            $status = $conversation->status()->first();

            // Ver comentario equivalente en close()/reopen(): sin este dispatch,
            // cambiar el estado desde el desplegable "Estado" del panel derecho no
            // dejaba rastro en la pestaña "Actividad" ni notificaba al widget del
            // cliente en tiempo real (único listener: LogActivityOnConversation
            // StatusChanged, que escucha ConversationStatusChanged, no el genérico
            // ConversationUpdated que dispara el observer).
            if ($statusChanged && $status) {
                ConversationStatusChanged::dispatch($conversation, $status, auth()->id());
            }

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_updated'),
                'status' => [
                    'id' => $status?->id,
                    'name' => $status?->name,
                    'color' => $status?->color,
                ],
            ]);
        }

        if ($request->has('assignee_id')) {
            $assigneeId = $request->validated()['assignee_id'] ?? null;

            if ($assigneeId) {
                $conversation->assignTo($assigneeId);
            } else {
                $conversation->assignee_id = null;
                $conversation->assigned_at = null;
                $conversation->save();
            }

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.assignment_updated'),
            ]);
        }

        if ($request->has('group_id')) {
            $oldGroupId = $conversation->group_id;
            $newGroupId = $request->validated()['group_id'] ?? null;
            $conversation->group_id = $newGroupId;
            $conversation->save();

            // Remove old group tag if group changed
            if ($oldGroupId && $oldGroupId != $conversation->group_id) {
                $oldGroup = Group::find($oldGroupId);
                if ($oldGroup?->tag_id) {
                    $conversation->conversationTags()->detach($oldGroup->tag_id);
                }
            }

            // Attach new group tag
            $group = null;
            if ($conversation->group_id) {
                $group = Group::with('users')->find($conversation->group_id);
                if ($group?->tag_id) {
                    $conversation->conversationTags()->syncWithoutDetaching([$group->tag_id]);
                }
            }

            // Este endpoint (modal "Mover a equipo" del panel derecho) solo movía
            // group_id + las tags, sin pasar por Conversation::assignToGroup() —
            // por eso no se notificaba a los miembros del equipo ni quedaba
            // registro en la pestaña "Actividad" (ActivityMessageService::
            // logTeamAssigned() existía pero nadie la invocaba).
            if ($group && $newGroupId != $oldGroupId) {
                foreach ($group->users as $member) {
                    event(new InboxItemChanged($conversation->id, $member->id, 'assigned'));
                    $member->notify(new ConversationAssignedNotification($conversation));
                }

                $this->activityMessages->logTeamAssigned(
                    $conversation,
                    $group->id,
                    $group->name,
                    auth()->user()
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Conversación movida al equipo correctamente.',
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified conversation (soft delete)
     */
    public function destroy(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $conversation);

        $conversation->delete();
        $conversation->broadcastInboxChanged('deleted');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_deleted'),
            ]);
        }

        return redirect()->route('manager.helpdesk.conversations.index')
            ->with('success', __('helpdesk::helpdesk.messages.conversation_deleted'));
    }

    /**
     * Restore a soft-deleted conversation
     */
    public function restore(Request $request, $id): RedirectResponse|JsonResponse
    {
        $conversation = Conversation::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $conversation);

        $conversation->restore();
        $conversation->broadcastInboxChanged('restored');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_restored'),
            ]);
        }

        return redirect()->route('manager.helpdesk.conversations.index')
            ->with('success', __('helpdesk::helpdesk.messages.conversation_restored'));
    }

    /**
     * Close a conversation
     */
    public function close(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->close();

        ConversationClosed::dispatch($conversation);

        // close()/reopen() actualizan status_id con un update() directo en el
        // modelo, sin pasar por ConversationStatusChanged — el único listener
        // que registra el cambio en el audit log (LogActivityOnConversation
        // StatusChanged). Antes de este fix, cerrar/reabrir desde el inbox
        // web no dejaba ningún rastro en la pestaña "Actividad", solo el
        // cambio manual de estado vía la API v1 lo hacía.
        ConversationStatusChanged::dispatch($conversation, $conversation->fresh('status')->status, auth()->id());

        // Fire CSAT survey unless explicitly skipped (best effort — never block close).
        if (! $request->boolean('skip_csat')) {
            try {
                app(CsatService::class)->dispatchForConversation($conversation);
            } catch (\Throwable $e) {
                Log::channel('helpdesk')->warning('CSAT dispatch failed on close', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_closed'),
            ]);
        }

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_closed'));
    }

    /**
     * Reopen a conversation
     */
    public function reopen(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->reopen();

        // Ver comentario equivalente en close(): reopen() tampoco disparaba
        // ConversationStatusChanged, así que reabrir no quedaba registrado
        // en la pestaña "Actividad" de la conversación.
        ConversationStatusChanged::dispatch($conversation, $conversation->fresh('status')->status, auth()->id());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_reopened'),
            ]);
        }

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_reopened'));
    }

    /**
     * Archive a conversation
     */
    public function archive(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->archive();
        $conversation->broadcastInboxChanged('archived');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_archived'),
            ]);
        }

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_archived'));
    }

    /**
     * Unarchive a conversation
     */
    public function unarchive(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->unarchive();
        $conversation->broadcastInboxChanged('unarchived');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_unarchived'),
            ]);
        }

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_unarchived'));
    }

    /**
     * List merge candidates: other conversations from the same customer.
     */
    public function mergeCandidates(Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $candidates = Conversation::query()
            ->where('customer_id', $conversation->customer_id)
            ->where('id', '!=', $conversation->id)
            ->whereNull('deleted_at')
            ->with(['customer', 'status', 'lastMessage'])
            ->latest('last_message_at')
            ->limit(10)
            ->get()
            ->map(fn (Conversation $c) => [
                'id' => $c->id,
                'subject' => $c->subject ?? '#'.$c->id,
                'channel' => $c->channel ?? 'web',
                'channel_icon' => $c->channel_info['icon'],
                'preview' => mb_strimwidth(strip_tags((string) ($c->getLatestMessage()?->body ?? '')), 0, 80, '…'),
                'time' => $c->last_message_at?->diffForHumans() ?? $c->created_at?->diffForHumans() ?? '—',
                'status' => $c->status?->name,
            ]);

        return response()->json(['success' => true, 'data' => $candidates]);
    }

    /**
     * Merge current conversation into a target: moves items then soft-deletes current.
     */
    public function merge(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $targetId = (int) $request->input('target_id');

        if ($targetId === $conversation->id) {
            return response()->json(['success' => false, 'message' => __('helpdesk::helpdesk.messages.merge_self')], 422);
        }

        $target = Conversation::findOrFail($targetId);

        // La conversación destino recibe los mensajes: el agente debe poder
        // actualizarla y tener acceso a su inbox (ConversationPolicy::update()
        // incluye canAccessInbox()), no solo al inbox del origen.
        $this->authorize('update', $target);

        if ($target->customer_id !== $conversation->customer_id) {
            return response()->json(['success' => false, 'message' => __('helpdesk::helpdesk.messages.merge_different_customer')], 422);
        }

        \DB::transaction(function () use ($conversation, $target): void {
            $conversation->items()->update(['conversation_id' => $target->id]);
            $conversation->delete();
        });

        return response()->json([
            'success' => true,
            'message' => __('helpdesk::helpdesk.messages.merge_success'),
            'target_id' => $target->id,
        ]);
    }

    /**
     * Re-link a conversation to a different, already existing customer. Used
     * when the automatic match failed (e.g. a WhatsApp-created customer with
     * no email that doesn't match the real contact record).
     */
    public function linkCustomer(LinkConversationCustomerRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $newCustomer = Customer::findOrFail($request->validated('customer_id'));

        if ($newCustomer->id === $conversation->customer_id) {
            return response()->json(['success' => false, 'message' => __('helpdesk::helpdesk.messages.link_customer_same')], 422);
        }

        DB::transaction(function () use ($conversation, $newCustomer): void {
            $conversation->update(['customer_id' => $newCustomer->id]);
            $newCustomer->incrementConversationCount();
        });

        return response()->json([
            'success' => true,
            'message' => __('helpdesk::helpdesk.messages.link_customer_success'),
            'customer' => [
                'id' => $newCustomer->id,
                'name' => $newCustomer->name,
                'email' => $newCustomer->email,
                'phone' => $newCustomer->phone ?: $newCustomer->whatsapp_phone,
            ],
        ]);
    }

    /**
     * Store a new message in a conversation
     */
    public function storeMessage(StoreConversationMessageRequest $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();

        // WhatsApp: fuera de la ventana de servicio de 24h Meta solo admite
        // plantillas (HSM), no texto libre. Se rechaza aquí para no gastar un
        // envío que Meta rechazaría y para darle el motivo al agente.
        $hasContent = filled($validated['body'] ?? null) || $request->hasFile('attachments');
        if ($hasContent && ! $request->boolean('is_internal') && ! $conversation->isWhatsAppWindowOpen()) {
            return response()->json([
                'success' => false,
                'message' => __('helpdesk::helpdesk.inbox.thread.wa_window_closed'),
                'wa_window_closed' => true,
            ], 422);
        }

        // Solo se acepta el ítem citado si pertenece A ESTA conversación (defensa
        // en profundidad junto a la regla exists() del Form Request): evita
        // persistir/filtrar un reply_to_id de otra conversación o inbox (IDOR).
        $replyItem = ! empty($validated['reply_to_id'])
            ? $conversation->items()->find($validated['reply_to_id'])
            : null;

        [$item, $successMessage] = app(ConversationMessageService::class)->store($conversation, [
            'body' => $validated['body'] ?? '',
            'is_internal' => $request->boolean('is_internal'),
            'attachments' => $request->file('attachments', []),
            'action' => $request->input('action'),
            'reply_to_id' => $replyItem?->id,
        ]);

        $replyMeta = null;
        if ($replyItem) {
            $replyAuthor = $replyItem->user?->name
                ?? $replyItem->author?->name
                ?? $conversation->customer?->name
                ?? 'Cliente';
            $replyMeta = [
                'id' => $replyItem->id,
                'author' => $replyAuthor,
                'body' => Str::limit($replyItem->body, 80),
            ];
        }

        if ($request->wantsJson()) {
            // Preview OpenGraph (si hay URL): GenerateLinkPreviewJob lo genera
            // fuera del hilo HTTP y re-emite ConversationMessageCreated al
            // terminar (el panel reemplaza la burbuja existente por id) —
            // antes había aquí un fetch síncrono de hasta 6s como fallback,
            // reintroduciendo el mismo bloqueo que ese job existe para evitar.
            if ($item) {
                $reloaded = ConversationItem::with('user')->find($item->id);
                if ($reloaded) {
                    $item = $reloaded;
                }
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'item' => [
                    'id' => $item?->id,
                    'body' => $item?->body,
                    'is_internal' => (bool) $item?->is_internal,
                    'created_at' => $item?->created_at?->toIso8601String(),
                    'time' => $item?->created_at?->format('H:i'),
                    'author' => $item?->user?->firstname
                        ? trim($item->user->firstname.' '.$item->user->lastname)
                        : (auth()->user()?->firstname
                            ? trim(auth()->user()->firstname.' '.auth()->user()->lastname)
                            : null),
                    'is_outgoing' => true,
                    'reply_to' => $replyMeta,
                    'attachment_urls' => $item?->attachment_urls ?? [],
                    'metadata' => $item?->metadata ?? [],
                ],
            ], 201);
        }

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', $successMessage);
    }

    /**
     * Sirve un attachment como descarga forzada con Content-Disposition.
     * Usado por el lightbox y el menú "Descargar" del bubble.
     *
     * Recibe ?url= con la URL completa o solo el path /storage/... y devuelve
     * el archivo bajo la disk public con el filename original.
     */
    /**
     * Toggle a reaction (emoji) on a conversation item. The agent can react
     * with one of a fixed set of emojis; reacting with the same emoji again
     * removes it.
     */
    public function reactToMessage(Request $request, ConversationItem $item): JsonResponse
    {
        $this->assertItemAccess($item);

        $emoji = (string) $request->input('emoji', '');
        if ($emoji === '') {
            return response()->json(['success' => false, 'message' => 'Emoji requerido'], 422);
        }

        $userId = (int) auth()->id();
        $metadata = $item->metadata ?? [];
        $reactions = collect($metadata['reactions'] ?? []);

        $existing = $reactions->first(
            fn ($r) => ($r['user_id'] ?? null) === $userId && ($r['emoji'] ?? null) === $emoji
        );

        if ($existing) {
            $reactions = $reactions->reject(
                fn ($r) => ($r['user_id'] ?? null) === $userId && ($r['emoji'] ?? null) === $emoji
            )->values();
            $action = 'removed';
        } else {
            $reactions = $reactions->reject(fn ($r) => ($r['user_id'] ?? null) === $userId)->values();
            $reactions->push([
                'user_id' => $userId,
                'emoji' => $emoji,
                'at' => now()->toIso8601String(),
            ]);
            $action = 'added';
        }

        $metadata['reactions'] = $reactions->values()->all();
        $item->metadata = $metadata;
        $item->save();

        return response()->json([
            'success' => true,
            'action' => $action,
            'emoji' => $emoji,
            'reactions' => $metadata['reactions'],
        ]);
    }

    /**
     * Forward an existing message to another customer (creates or reuses a
     * conversation with that customer and posts a copy of the body and
     * attachments).
     */
    public function forwardMessage(Request $request, ConversationItem $item): JsonResponse
    {
        $this->assertItemAccess($item);

        $customerId = (int) $request->input('customer_id');
        if ($customerId <= 0) {
            return response()->json(['success' => false, 'message' => 'Cliente requerido'], 422);
        }

        $targetCustomer = Customer::find($customerId);
        if (! $targetCustomer) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $sourceConv = $item->conversation;
        $defaultStatus = ConversationStatus::query()->where('is_default', true)->first()
            ?? ConversationStatus::query()->where('slug', 'open')->first();

        $targetConv = Conversation::firstOrCreate(
            [
                'customer_id' => $customerId,
                'status_id' => $defaultStatus?->id,
                'inbox_id' => $sourceConv?->inbox_id,
            ],
            [
                'channel' => $sourceConv?->channel ?? 'web',
                'priority' => 'normal',
                'last_message_at' => now(),
            ]
        );

        $forwarded = $targetConv->items()->create([
            'type' => 'message',
            'body' => $item->body,
            'html_body' => $item->html_body,
            'attachment_urls' => $item->attachment_urls,
            'metadata' => array_merge($item->metadata ?? [], [
                'forwarded_from' => $item->id,
                'forwarded_by' => auth()->id(),
                'forwarded_at' => now()->toIso8601String(),
            ]),
            'user_id' => auth()->id(),
            'is_internal' => false,
        ]);

        $targetConv->update(['last_message_at' => now()]);

        $this->deliverForwardedItem($targetConv, $forwarded);

        broadcast(new ConversationMessageCreated($forwarded, false))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Mensaje reenviado a '.($targetCustomer->name ?? $targetCustomer->email),
            'conversation_id' => $targetConv->id,
            'item_id' => $forwarded->id,
        ]);
    }

    /**
     * Deliver a forwarded item to the destination conversation's external channel
     * (whatsapp/facebook/instagram). Web/broadcast conversations are skipped.
     * Failures are logged and never break the forwarding flow.
     */
    private function deliverForwardedItem(Conversation $targetConv, ConversationItem $forwarded): void
    {
        $outbound = app(OutboundMessageService::class);
        if (! $outbound->supports($targetConv)) {
            return;
        }

        $externalId = null;

        $body = trim((string) $forwarded->body);
        if ($body !== '') {
            try {
                $externalId = $outbound->sendReply($targetConv, $body);
            } catch (\Throwable $e) {
                Log::channel('helpdesk')->error('forwardMessage: outbound send failed', [
                    'conversation_id' => $targetConv->id,
                    'channel' => $targetConv->channel,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ((array) $forwarded->attachment_urls as $att) {
            $url = is_array($att) ? ($att['url'] ?? null) : $att;
            if (blank($url)) {
                continue;
            }

            try {
                $sent = $outbound->sendAttachment(
                    $targetConv,
                    is_array($att) ? (string) ($att['type'] ?? 'file') : 'file',
                    $this->absoluteUrl((string) $url),
                    null,
                    is_array($att) ? ($att['name'] ?? null) : null,
                );
                $externalId ??= $sent;
            } catch (\Throwable $e) {
                Log::channel('helpdesk')->error('forwardMessage: outbound attachment send failed', [
                    'conversation_id' => $targetConv->id,
                    'channel' => $targetConv->channel,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($externalId) {
            $forwarded->external_id = $externalId;
            $forwarded->save();
        }
    }

    /**
     * Return delivery / read / metadata info for a single message.
     */
    public function messageInfo(ConversationItem $item): JsonResponse
    {
        $this->assertItemAccess($item);

        $item->loadMissing(['user', 'author', 'conversation']);
        $meta = $item->metadata ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $item->id,
                'sent_at' => $item->created_at?->toIso8601String(),
                'delivered_at' => $meta['customer_delivered_at'] ?? null,
                'read_at' => $meta['customer_read_at'] ?? null,
                'author_name' => $item->user?->name ?? $item->author?->name ?? '—',
                'channel' => $item->conversation?->channel ?? '—',
                'external_id' => $item->external_id ?? '—',
                'is_internal' => (bool) $item->is_internal,
                'reactions' => $meta['reactions'] ?? [],
                'updated_at' => $item->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Snooze a conversation until a given datetime.
     */
    public function snooze(SnoozeConversationRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $until = $request->date('until');

        $conversation->update([
            'snoozed_until' => $until,
            'snoozed_by' => auth()->id(),
        ]);

        $conversation->broadcastInboxChanged('snoozed');

        UnsnoozeConversationJob::dispatch($conversation)->delay($until);

        return response()->json([
            'success' => true,
            'message' => 'Conversación pospuesta hasta '.$until->format('d/m/Y H:i').'.',
            'snoozed_until' => $until->toIso8601String(),
        ]);
    }

    /**
     * Toggle pin for the current user on this conversation.
     */
    public function togglePin(Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $userId = auth()->id();

        $meta = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('user_id', $userId)
            ->where('conversation_id', $conversation->id)
            ->first();

        $pinned = false;

        if ($meta) {
            $pinned = $meta->pinned_at === null;
            DB::connection('helpdesk')
                ->table('helpdesk_user_conversation_meta')
                ->where('id', $meta->id)
                ->update(['pinned_at' => $pinned ? now() : null, 'updated_at' => now()]);
        } else {
            DB::connection('helpdesk')
                ->table('helpdesk_user_conversation_meta')
                ->insert([
                    'user_id' => $userId,
                    'conversation_id' => $conversation->id,
                    'pinned_at' => now(),
                    'muted_until' => null,
                    'blocked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $pinned = true;
        }

        return response()->json([
            'success' => true,
            'pinned' => $pinned,
            'message' => $pinned ? 'Conversación fijada.' : 'Conversación desfijada.',
        ]);
    }

    /**
     * Toggle mute for the current user on this conversation.
     */
    public function toggleMute(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $userId = auth()->id();
        $until = $request->filled('until') ? $request->date('until') : now()->addDays(7);

        $meta = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('user_id', $userId)
            ->where('conversation_id', $conversation->id)
            ->first();

        $muted = false;

        if ($meta) {
            $muted = $meta->muted_until === null || now()->greaterThan($meta->muted_until);
            DB::connection('helpdesk')
                ->table('helpdesk_user_conversation_meta')
                ->where('id', $meta->id)
                ->update([
                    'muted_until' => $muted ? $until : null,
                    'updated_at' => now(),
                ]);
        } else {
            DB::connection('helpdesk')
                ->table('helpdesk_user_conversation_meta')
                ->insert([
                    'user_id' => $userId,
                    'conversation_id' => $conversation->id,
                    'pinned_at' => null,
                    'muted_until' => $until,
                    'blocked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $muted = true;
        }

        return response()->json([
            'success' => true,
            'muted' => $muted,
            'message' => $muted ? 'Conversación silenciada.' : 'Conversación reactivada.',
        ]);
    }

    /**
     * Block the contact associated with a conversation.
     */
    public function blockContact(Conversation $conversation): JsonResponse
    {
        $this->authorize('delete', $conversation);

        $customer = Customer::find($conversation->customer_id);

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Contacto no encontrado.'], 404);
        }

        $customer->update([
            'banned_at' => now(),
            'ban_reason' => 'blocked from inbox',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contacto bloqueado correctamente.',
        ]);
    }

    /**
     * Schedule a message to be sent at a future datetime.
     */
    public function storeScheduledMessage(StoreScheduledMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();
        $scheduledAt = Carbon::parse($validated['scheduled_at']);

        [$item] = app(ConversationMessageService::class)->store($conversation, array_merge($validated, [
            'scheduled_at' => $scheduledAt,
            'metadata' => array_merge($validated['metadata'] ?? [], [
                'scheduled' => true,
                'scheduled_by' => auth()->id(),
            ]),
        ]));

        SendScheduledMessageJob::dispatch($item)->delay($scheduledAt);

        return response()->json([
            'success' => true,
            'message' => 'Mensaje programado para '.$scheduledAt->format('d/m/Y H:i').'.',
            'item' => [
                'id' => $item->id,
                'body' => $item->body,
                'scheduled_at' => $scheduledAt->toIso8601String(),
                'scheduled_at_formatted' => $scheduledAt->format('d/m/Y H:i'),
            ],
        ], 201);
    }

    private function absoluteUrl(string $url): string
    {
        $base = rtrim(config('helpdesk.public_url') ?? config('app.url'), '/');
        $appUrl = rtrim((string) config('app.url'), '/');

        if (preg_match('#^https?://#i', $url)) {
            if ($appUrl && $base !== $appUrl && str_starts_with($url, $appUrl.'/')) {
                return $base.substr($url, strlen($appUrl));
            }

            return $url;
        }

        return $base.'/'.ltrim($url, '/');
    }

    /**
     * Mark a conversation as spam, archive it, and ban the customer.
     */
    public function markSpam(MarkSpamRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $reason = $request->validated()['reason'] ?? null;

        DB::transaction(function () use ($conversation): void {
            $conversation->update([
                'is_spam' => true,
                'is_archived' => true,
            ]);

            $customer = Customer::find($conversation->customer_id);
            if ($customer) {
                $customer->update([
                    'banned_at' => now(),
                    'ban_reason' => 'spam',
                ]);
            }
        });

        $conversation->broadcastInboxChanged('spam');

        Log::channel('helpdesk')->info('Conversation marked as spam', [
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'reason' => $reason,
        ]);

        event(new ConversationMarkedAsSpam($conversation, auth()->id()));

        return response()->json([
            'success' => true,
            'message' => 'Conversación marcada como spam.',
        ]);
    }

    /**
     * Apply sort order to the conversations query based on the sort param.
     *
     * Supported values: newest (default), oldest, priority, unassigned, unread
     */
    private function applySortOrder(Builder $query, string $sort, int $userId): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('last_message_at'),
            'priority' => $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')"),
            'unassigned' => $query->orderByRaw('assignee_id IS NOT NULL ASC')->orderBy('last_message_at', 'desc'),
            'unread' => $query->orderByRaw(
                '(SELECT COUNT(*) FROM helpdesk_conversation_reads WHERE conversation_id = helpdesk_conversations.id AND user_id = ?) = 0 DESC',
                [$userId]
            )->orderBy('last_message_at', 'desc'),
            default => $query->orderBy('last_message_at', 'desc'),
        };
    }

    /**
     * Kanban board view grouped by status
     */
    public function kanban(): View
    {
        $this->authorize('viewAny', Conversation::class);

        $statuses = ConversationStatus::active()->ordered()->get();

        $userInboxIds = $this->getUserInboxIds();

        $conversations = Conversation::query()
            ->with(['customer', 'status'])
            ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds))
            ->withoutActiveBot()
            ->where('is_spam', false)
            ->where('is_archived', false)
            ->orderByDesc('last_message_at')
            ->limit(200)
            ->get();

        $byStatus = $conversations->groupBy('status_id');

        return view('helpdesk::helpdesk.inbox.kanban', compact('statuses', 'byStatus'));
    }

    public function sendCsatSurvey(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $csatService = app(CsatService::class);
        $rating = $csatService->dispatchForConversation($conversation);

        if ($rating === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar la encuesta. El canal de esta conversación no es compatible.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Encuesta CSAT enviada al cliente.',
        ]);
    }

    /**
     * List internal notes (is_internal=true) for a conversation.
     */
    public function internalNotes(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $notes = $conversation->items()
            ->where('is_internal', true)
            ->with('user')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(fn (ConversationItem $item) => [
                'id' => $item->id,
                'body' => $item->body,
                'author' => $item->user?->name ?? 'Agente',
                'initials' => $this->getInitials($item->user?->name ?? 'Agente'),
                'created_at' => $item->created_at?->toIso8601String(),
                'time_ago' => $item->created_at?->diffForHumans() ?? '—',
                'is_pinned' => (bool) ($item->metadata['is_pinned'] ?? false),
            ]);

        return response()->json(['success' => true, 'notes' => $notes]);
    }

    /**
     * List approved WhatsApp HSM templates.
     */
    public function hsmTemplates(): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $templates = WhatsAppTemplate::query()
            ->where('status', 'approved')
            ->orderBy('display_name')
            ->get()
            ->map(fn (WhatsAppTemplate $t) => [
                'id' => $t->id,
                'name' => $t->display_name,
                // Nombre técnico registrado en Meta (WhatsAppTemplate::external_id) — es
                // lo que exige la Cloud API en template.name. display_name es solo la
                // etiqueta amigable para la UI; mandarla a Meta devuelve 132001.
                'external_id' => $t->external_id,
                'body' => $t->body_template,
                'category' => $t->category,
                'header_type' => $t->header_type,
                'header_value' => $t->header_value,
                'footer_text' => $t->footer_text,
                'language' => $t->language,
                'param_count' => $t->param_count,
            ]);

        return response()->json(['success' => true, 'templates' => $templates]);
    }

    /**
     * Paginación "cargar anteriores" del hilo. Devuelve los ~50 items
     * inmediatamente anteriores a {before} (id) en orden ascendente para que el
     * frontend los pueda prepend directamente. El hilo principal solo carga los
     * últimos 50 (ver index()), por lo que este endpoint sirve el histórico.
     */
    public function olderItems(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->loadMissing('customer');
        $beforeId = $request->integer('before');

        // Pedimos 51 para saber si quedan más anteriores sin un COUNT extra.
        $batch = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('type', '!=', 'email_sent')
            ->when($beforeId > 0, fn ($q) => $q->where('id', '<', $beforeId))
            ->with(['user:id,firstname,lastname'])
            ->latest('id')
            ->limit(51)
            ->get();

        $hasMore = $batch->count() > 50;

        $customerName = $conversation->customer?->name ?? 'Cliente';

        $items = $batch->take(50)
            ->sortBy('id')
            ->values()
            ->map(fn (ConversationItem $item): array => [
                'id' => $item->id,
                'type' => $item->type,
                'body' => $item->body,
                'body_html' => $item->body_html,
                'is_internal' => (bool) $item->is_internal,
                'is_outgoing' => (bool) $item->user_id,
                'author' => $item->user_id
                    ? ($item->user?->fullName() ?: 'Agente')
                    : $customerName,
                'time' => $item->created_at?->format('H:i'),
                'created_at' => $item->created_at?->toIso8601String(),
                'attachment_urls' => $item->attachment_urls ?? [],
                'metadata' => $item->metadata ?? [],
            ])
            ->all();

        return response()->json([
            'items' => $items,
            'has_more' => $hasMore,
        ]);
    }

    public function conversationViewerItems(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->loadMissing(['customer', 'assignee', 'status', 'inbox']);

        $channelIcons = [
            'web' => 'far fa-comment-dots',
            'whatsapp' => 'fab fa-whatsapp',
            'facebook' => 'fab fa-facebook-f',
            'instagram' => 'fab fa-instagram',
            'email' => 'far fa-envelope',
            'twitter' => 'fab fa-twitter',
        ];

        $customer = $conversation->customer;
        $custName = $customer?->name ?? 'Cliente';
        $custInit = $this->getInitials($custName);
        $assignee = $conversation->assignee;
        $agentInit = $assignee ? $this->getInitials($assignee->name ?? '') : 'A';

        $items = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->whereNotIn('type', ['email_sent', 'event'])
            ->with(['user:id,firstname,lastname'])
            ->oldest('created_at')
            ->limit(200)
            ->get();

        $mapped = $items->map(function (ConversationItem $item) use ($custInit, $custName) {
            $isAgent = (bool) $item->user_id;
            $authorName = $isAgent
                ? $item->user->fullName() ?: 'Agente'
                : $custName;
            $initials = $isAgent ? $this->getInitials($authorName) : $custInit;
            $body = $item->type === 'activity'
                ? ($item->content ?? $item->body ?? '')
                : ($item->body ?? '');

            return [
                'id' => $item->id,
                'type' => $item->type,
                'body' => $body,
                'is_agent' => $isAgent,
                'is_internal' => $item->type === 'activity',
                'author_name' => $authorName,
                'author_initials' => $initials,
                'time_formatted' => $item->created_at?->format('H:i') ?? '',
                'date_label' => $this->dateLabelForItem($item->created_at),
                'attachment_urls' => $item->attachment_urls ?? [],
            ];
        });

        $grouped = [];
        $lastDate = null;

        foreach ($mapped as $m) {
            if ($m['date_label'] !== $lastDate) {
                $grouped[] = ['type' => 'day_separator', 'label' => $m['date_label']];
                $lastDate = $m['date_label'];
            }
            $grouped[] = $m;
        }

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'subject' => $conversation->subject ?? "Conversación #{$conversation->id}",
                'channel' => $conversation->channel ?? 'web',
                'channel_icon' => $channelIcons[$conversation->channel ?? 'web'] ?? 'far fa-comment-dots',
                'status_name' => $conversation->status?->name ?? 'Abierta',
                'is_open' => (bool) ($conversation->status?->is_open ?? true),
                'started_at_formatted' => $conversation->created_at?->format('d/m H:i') ?? '',
                'message_count' => $items->count(),
                'customer_name' => $custName,
                'customer_initials' => $custInit,
                'agent_name' => $assignee?->name ?? '',
                'agent_initials' => $agentInit,
            ],
            'items' => $grouped,
        ]);
    }

    public function auditLog(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->loadMissing(['customer', 'assignee']);

        $entries = [];

        // Conversation created
        $entries[] = [
            'ts' => $conversation->created_at?->format('d/m H:i') ?? '—',
            'ts_raw' => $conversation->created_at?->timestamp ?? 0,
            'action' => 'Conversación creada',
            'who' => 'Sistema · vía '.ucfirst($conversation->channel ?? 'widget'),
            'tag' => 'create',
            'category' => 'system',
        ];

        // Activity + message items
        $items = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->with(['user:id,firstname,lastname'])
            ->oldest('created_at')
            ->limit(500)
            ->get();

        foreach ($items as $item) {
            $agentName = $item->user
                ? $item->user->fullName() ?: 'Agente'
                : 'Sistema';

            if ($item->type === 'activity') {
                $body = trim(strip_tags($item->body ?? $item->content ?? ''));
                if (! $body) {
                    continue;
                }
                $bodyLower = mb_strtolower($body);
                $tag = match (true) {
                    str_contains($bodyLower, 'asigna') => 'assign',
                    str_contains($bodyLower, 'etiqueta') => 'tag',
                    str_contains($bodyLower, 'cerrada')
                        || str_contains($bodyLower, 'reabierta')
                        || str_contains($bodyLower, 'archivada')
                        || str_contains($bodyLower, 'estado') => 'state',
                    str_contains($bodyLower, 'prioridad') => 'update',
                    default => 'update',
                };
                $category = match ($tag) {
                    'assign' => 'assign',
                    'tag' => 'tag',
                    'state' => 'state',
                    default => 'update',
                };
                $entries[] = [
                    'ts' => $item->created_at?->format('d/m H:i') ?? '—',
                    'ts_raw' => $item->created_at?->timestamp ?? 0,
                    'action' => $body,
                    'who' => $agentName,
                    'tag' => $tag,
                    'category' => $category,
                ];

                continue;
            }

            if (in_array($item->type, ['message', 'incoming', 'outgoing', 'email_sent'], true)) {
                $entries[] = [
                    'ts' => $item->created_at?->format('d/m H:i') ?? '—',
                    'ts_raw' => $item->created_at?->timestamp ?? 0,
                    'action' => match ($item->type) {
                        'incoming' => 'Mensaje recibido del cliente',
                        'outgoing' => 'Mensaje enviado al cliente',
                        'email_sent' => 'Email enviado al cliente',
                        default => $item->user_id ? 'Respuesta del agente' : 'Mensaje del cliente',
                    },
                    'who' => $agentName,
                    'tag' => 'message',
                    'category' => 'message',
                ];
            }
        }

        usort($entries, fn ($a, $b) => $a['ts_raw'] <=> $b['ts_raw']);

        $counts = [
            'all' => count($entries),
            'assign' => count(array_filter($entries, fn ($e) => $e['category'] === 'assign')),
            'state' => count(array_filter($entries, fn ($e) => $e['category'] === 'state')),
            'tag' => count(array_filter($entries, fn ($e) => $e['category'] === 'tag')),
            'message' => count(array_filter($entries, fn ($e) => $e['category'] === 'message')),
            'update' => count(array_filter($entries, fn ($e) => $e['category'] === 'update')),
        ];

        foreach ($entries as &$e) {
            unset($e['ts_raw'], $e['category']);
        }

        return response()->json([
            'id' => $conversation->id,
            'subject' => $conversation->subject ?? "Conversación #{$conversation->id}",
            'counts' => $counts,
            'entries' => $entries,
        ]);
    }

    private function dateLabelForItem(?Carbon $dt): string
    {
        if (! $dt) {
            return 'Sin fecha';
        }

        if ($dt->isToday()) {
            return 'Hoy';
        }

        if ($dt->isYesterday()) {
            return 'Ayer';
        }

        return $dt->translatedFormat('D, d M');
    }

    private function getInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $initials = collect($parts)->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');

        return mb_strtoupper($initials);
    }
}

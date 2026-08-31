<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Filters\TicketFilter;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Http\Requests\StoreTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\UpdateTicketRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketRead;
use Modules\HelpdeskTickets\Models\TicketReview;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Models\TicketTemplate;
use Modules\HelpdeskTickets\Models\TicketView;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Modules\HelpdeskTickets\Services\TicketUpdateService;
use Modules\HelpdeskTickets\Services\TicketVariableInterpolator;

class TicketsCrudController extends Controller
{
    public function __construct(
        private readonly TicketUpdateService $ticketUpdateService,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        abort_if(! helpdesk_tickets_enabled(), 404);

        $userId = auth()->id();

        $views = TicketView::forUser($userId)->ordered()->limit(100)->get();

        $currentView = null;
        if ($request->has('viewId')) {
            $currentView = $views->firstWhere('id', $request->viewId);
        }

        if (! $currentView) {
            $currentView = $views->firstWhere('is_default', true) ?? $views->first();
        }

        $filter = new TicketFilter($request);

        $query = Ticket::query()
            ->with(['customer', 'status', 'category', 'group', 'assignee'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->whereDoesntHave(
                'reads',
                fn ($q2) => $q2->where('user_id', $userId)
            ), 'messages as message_count'])
            ->latest();

        // TicketView::applyFilters() (no Filter::applyViewFilters()) es quien
        // entiende las claves reales que guarda tickets-app.js#saveCurrentView()
        // (mine/unassigned/assignee_id, tags, created_from/created_to) — ya
        // están cubiertas por TicketViewFiltersTest, solo faltaba usarlo aquí.
        if ($currentView && ! empty($currentView->filters)) {
            $currentView->applyFilters($query, $userId);
        }

        $filter->apply($query);

        // Filtro de etiquetas — el mockup lo tiene como quinto filtro rápido
        // (junto a Origen/Categoría/Agente/Prioridad) pero el TicketFilter
        // compartido (Modules\Helpdesk\Filters\TicketFilter, usado también
        // por otros módulos) no soporta 'tags'; se aplica aquí en vez de
        // tocar esa clase compartida, para no arriesgar otros consumidores.
        // Mismo parámetro 'tag' para el <select> rápido de la barra (un solo
        // valor) y para el campo "Separadas por coma" del modal "Más
        // filtros" (varias etiquetas, todas obligatorias — AND).
        if ($request->filled('tag')) {
            $tags = array_filter(array_map('trim', explode(',', $request->string('tag')->toString())));

            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // Los tickets pospuestos (snooze activo) salen de la cola salvo que se
        // pidan explícitamente con ?snoozed=1 (vista "Pospuestos").
        $request->boolean('snoozed') ? $query->snoozed() : $query->notSnoozed();

        // Orden por SLA más urgente (mockup): los que tienen fecha de
        // vencimiento más próxima primero; los que no tienen SLA (null) van
        // al final en vez de intercalarse al azar.
        if ($request->get('sort') === 'sla') {
            $query->reorder()->orderByRaw('sla_resolution_due_at IS NULL')->orderBy('sla_resolution_due_at');
        }

        $tickets = $query->paginate(50)->appends($request->query());
        $statuses = CatalogCacheService::statuses();
        $categories = CatalogCacheService::categories();
        $groups = CatalogCacheService::groups();
        $agents = CatalogCacheService::agents();
        // Cacheado: esto era un pluck('tags') sobre TODA la tabla en cada carga
        // del listado, solo para rellenar el desplegable de etiquetas. Las
        // etiquetas cambian poco y la lista es la misma para todos los agentes,
        // así que no hay motivo para recalcularla por request. La invalida
        // updateTags() al guardar (CatalogCacheService::invalidateTags()).
        $availableTags = CatalogCacheService::ticketTags();
        // Para insertar plantilla en la caja de respuesta del Hilo — mismo
        // criterio que showFull() (globales o del propio usuario, activas).
        $cannedReplies = TicketCannedReply::where('is_active', true)
            ->where(fn ($q) => $q->where('is_global', true)->orWhere('user_id', $userId))
            ->orderBy('title')
            ->get(['id', 'title', 'content', 'short_code']);

        // Ticket preseleccionado (llega vía ?ticket=, típicamente del redirect
        // de show()): se busca aparte porque puede no estar en la página/filtro
        // actual, igual que ConversationsController::buildConversationPaneData().
        $selectedTicket = null;
        if ($selectedId = $request->integer('ticket')) {
            $selectedTicket = Ticket::query()
                ->with(['customer', 'status', 'category', 'assignee'])
                ->withCount(['messages as unread_count' => fn ($q) => $q->whereDoesntHave(
                    'reads',
                    fn ($q2) => $q2->where('user_id', $userId)
                )])
                ->find($selectedId);

            if ($selectedTicket) {
                $this->authorize('view', $selectedTicket);
            }
        }

        return view('helpdesktickets::managers.tickets.index', [
            'tickets' => $tickets,
            'statuses' => $statuses,
            'categories' => $categories,
            'groups' => $groups,
            'agents' => $agents,
            'views' => $views,
            'currentView' => $currentView,
            'selectedTicket' => $selectedTicket,
            'availableTags' => $availableTags,
            'cannedReplies' => $cannedReplies,
            'filters' => $request->only(['status', 'category', 'assignee', 'group', 'priority', 'source', 'sla_status', 'search', 'archived', 'tag']),
            'tabCounts' => $this->tabCounts($userId),
        ]);
    }

    /**
     * Guardado rápido de vista personal (Fase D) — a diferencia de
     * Settings/TicketViewsController (gestión admin global, permiso
     * helpdesk.tickets.settings), este endpoint lo usa cualquier agente con
     * permiso de ver el listado; la vista queda scopeada a su propio
     * user_id (mismo criterio que TicketMailView para la bandeja de
     * emails).
     */
    public function storeView(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'filters' => 'nullable|array',
        ]);

        $view = TicketView::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'filters' => $validated['filters'] ?? [],
            'is_shared' => false,
            'is_system' => false,
        ]);

        return response()->json(['success' => true, 'view' => ['id' => $view->id, 'name' => $view->name]], 201);
    }

    /**
     * Conteos reales para los "quick filter" del app bar (Abiertos/Urgentes/
     * Míos/Sin asignar/En espera/Resueltos/Todos) — antes se calculaban en
     * tickets.js contando solo las filas ya cargadas en la página actual
     * (recountFilters()), lo que da números correctos por casualidad
     * mientras el total de tickets quepa en una sola página de 50. Aquí se
     * cuenta contra toda la tabla (respetando notSnoozed(), igual que el
     * listado), una sola pasada ligera sin hidratar modelos completos.
     *
     * @return array<string, int>
     */
    private function tabCounts(?int $userId): array
    {
        // Una sola agregación en vez de traerse la tabla entera. Antes esto era
        // ->get() sobre TODOS los tickets no pospuestos, hidratando un modelo
        // Eloquent por fila (más su relación status) para después contar con un
        // foreach en PHP: con cien mil tickets, cien mil objetos por cada carga
        // del listado. Los números que devuelve son exactamente los mismos.
        //
        // La agrupación de estados sale del CATÁLOGO (decenas de filas,
        // cacheado), no de recorrer los tickets: Ticket::canonicalStatusSlug()
        // aplica la misma normalización de slugs que statusSlug() y de ahí
        // salen las listas de status_id por bucket.
        $statusIds = $this->statusIdsByCanonicalSlug();

        $inOrFalse = fn (string $slug) => empty($statusIds[$slug])
            ? '0'
            : 'status_id IN ('.implode(',', $statusIds[$slug]).')';

        $openIds = array_merge($statusIds['open'] ?? [], $statusIds['progress'] ?? []);
        $openExpr = $openIds === [] ? '0' : 'status_id IN ('.implode(',', $openIds).')';

        // slaRowKind() es 'breach' si hay flag de incumplimiento o si el
        // vencimiento ya pasó, y 'warn' si vence en menos de 60 minutos; la
        // unión de ambos es "hay vencimiento y está a menos de 60 minutos, o
        // ya hay flag".
        $slaRiskExpr = '(sla_resolution_breached = 1 OR sla_first_response_breached = 1'
            .' OR (sla_resolution_due_at IS NOT NULL AND sla_resolution_due_at < ?))';

        $row = Ticket::query()
            ->notSnoozed()
            ->selectRaw(implode(', ', [
                'COUNT(*) AS c_all',
                "SUM(CASE WHEN {$openExpr} THEN 1 ELSE 0 END) AS c_open",
                "SUM(CASE WHEN {$inOrFalse('pending')} THEN 1 ELSE 0 END) AS c_pending",
                "SUM(CASE WHEN {$inOrFalse('resolved')} THEN 1 ELSE 0 END) AS c_resolved",
                "SUM(CASE WHEN {$inOrFalse('closed')} THEN 1 ELSE 0 END) AS c_closed",
                "SUM(CASE WHEN priority = 'urgent' OR sla_resolution_breached = 1 OR sla_first_response_breached = 1 THEN 1 ELSE 0 END) AS c_urgent",
                'SUM(CASE WHEN assignee_id IS NULL THEN 1 ELSE 0 END) AS c_unassigned',
                'SUM(CASE WHEN assignee_id = ? THEN 1 ELSE 0 END) AS c_mine',
                "SUM(CASE WHEN {$slaRiskExpr} THEN 1 ELSE 0 END) AS c_sla_risk",
            ]), [$userId ?? 0, now()->addMinutes(60)])
            ->first();

        return [
            'open' => (int) ($row->c_open ?? 0),
            'urgent' => (int) ($row->c_urgent ?? 0),
            'mine' => (int) ($row->c_mine ?? 0),
            'unassigned' => (int) ($row->c_unassigned ?? 0),
            'pending' => (int) ($row->c_pending ?? 0),
            'resolved' => (int) ($row->c_resolved ?? 0),
            'closed' => (int) ($row->c_closed ?? 0),
            'sla_risk' => (int) ($row->c_sla_risk ?? 0),
            'all' => (int) ($row->c_all ?? 0),
        ];
    }

    /**
     * Catálogo de estados agrupado por slug canónico: ['open' => [1,4], ...].
     *
     * Cacheado una hora como el resto de catálogos, e invalidado por
     * CatalogCacheService::invalidate(). Incluye los estados inactivos a
     * propósito: un ticket con un estado desactivado sigue existiendo y tiene
     * que seguir contando en su bucket.
     *
     * @return array<string, array<int>>
     */
    private function statusIdsByCanonicalSlug(): array
    {
        return Cache::remember('helpdesk:catalogs:status-ids-by-slug', 3600, function () {
            return TicketStatus::query()
                ->get(['id', 'slug', 'name', 'is_open'])
                ->groupBy(fn (TicketStatus $s) => Ticket::canonicalStatusSlug($s))
                ->map(fn ($group) => $group->pluck('id')->all())
                ->all();
        });
    }

    public function create(Request $request)
    {
        $this->authorize('create', Ticket::class);

        $customer = null;
        if ($request->has('customer')) {
            $customer = Customer::findOrFail($request->customer);
        }

        $customers = Customer::orderBy('name')->limit(500)->get();
        $categories = CatalogCacheService::categories();
        $statuses = CatalogCacheService::statuses();
        $defaultStatus = $statuses->firstWhere('is_default', true) ?? $statuses->first();
        $slaPolicies = TicketSlaPolicy::active()->get();
        $groups = CatalogCacheService::groups();
        $agents = CatalogCacheService::agents();

        $templates = TicketTemplate::active()
            ->visibleTo($request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'subject', 'body', 'category_id', 'priority']);

        return view('helpdesktickets::managers.tickets.create', [
            'customer' => $customer,
            'customers' => $customers,
            'categories' => $categories,
            'statuses' => $statuses,
            'defaultStatus' => $defaultStatus,
            'slaPolicies' => $slaPolicies,
            'groups' => $groups,
            'agents' => $agents,
            'templates' => $templates,
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        // Defensa en profundidad + consistencia con create()/show()/update():
        // StoreTicketRequest::authorize() ya exige el permiso, pero el resto
        // del CRUD llama $this->authorize() explícitamente.
        $this->authorize('create', Ticket::class);

        DB::transaction(function () use ($request, &$ticket) {
            $data = $request->validated();

            if (! isset($data['sla_policy_id']) && isset($data['category_id'])) {
                $category = TicketCategory::find($data['category_id']);
                if ($category && $category->default_sla_policy_id) {
                    $data['sla_policy_id'] = $category->default_sla_policy_id;
                }
            }

            $ticket = Ticket::create($data);

            // Sustitucion de variables tipo {{ticket_number}}, {{customer_name}},
            // {{erp_saldo_pendiente}}... Se hace SIEMPRE aqui (no solo cuando
            // viene de una plantilla) porque {{ticket_number}} no existe hasta
            // despues de Ticket::create(). Mismo TicketVariableInterpolator que
            // usan Macros/canned replies (fuente unica de variables, 30-ago-2026
            // — antes habia un TicketTemplateVariableResolver aparte con
            // sintaxis {llave_simple} distinta, ya no existe). Best-effort: si
            // el ERP no responde, las variables erp_* quedan vacias, nunca
            // bloquea la creacion del ticket.
            $interpolator = app(TicketVariableInterpolator::class);
            $resolvedSubject = $interpolator->interpolate($ticket->subject, $ticket);
            $resolvedDescription = $interpolator->interpolate($ticket->description, $ticket);
            if ($resolvedSubject !== $ticket->subject || $resolvedDescription !== $ticket->description) {
                $ticket->update(['subject' => $resolvedSubject, 'description' => $resolvedDescription]);
            }

            if ($request->hasFile('attachments')) {
                $attachmentPaths = [];
                foreach ($request->file('attachments') as $file) {
                    $attachmentPaths[] = $file->store(
                        'helpdesk/tickets/'.$ticket->id,
                        config('helpdesk.attachments.disk', 'local')
                    );
                }

                if (! empty($resolvedDescription)) {
                    $ticket->items()->create([
                        'type' => 'message',
                        'user_id' => auth()->id(),
                        'body' => $resolvedDescription,
                        'attachment_urls' => $attachmentPaths,
                        'is_internal' => false,
                    ]);
                }
            } elseif (! empty($resolvedDescription)) {
                $ticket->items()->create([
                    'type' => 'message',
                    'user_id' => auth()->id(),
                    'body' => $resolvedDescription,
                    'is_internal' => false,
                ]);
            }

            broadcast(new TicketCreated($ticket));
        });

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_created', ['number' => $ticket->ticket_number]));
    }

    /**
     * URL corta /tickets/{ticket} — redirige al listado con el ticket
     * preseleccionado, igual que ConversationsController::show() hace con el
     * inbox de conversaciones. La ficha completa (side-conversations, horas,
     * fusión, enlaces, historial) sigue disponible en showFull().
     */
    public function show(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('view', $ticket);

        return redirect()->route('manager.helpdesk.tickets.index', array_merge(
            ['ticket' => $ticket->id],
            $request->only(['viewId', 'status', 'search'])
        ));
    }

    /**
     * PATCH de etiquetas del ticket — mismo patrón que
     * TicketMailsController::updateTags().
     */
    public function tags(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'add' => 'nullable|string|max:100',
            'remove' => 'nullable|string|max:100',
        ]);

        $tags = collect($ticket->tags ?? []);

        if (! empty($validated['add'])) {
            $tags = $tags->push($validated['add'])->unique()->values();
        }
        if (! empty($validated['remove'])) {
            $tags = $tags->reject(fn ($t) => $t === $validated['remove'])->values();
        }

        $ticket->update(['tags' => $tags->all()]);

        // El desplegable de etiquetas del listado sale de una lista cacheada:
        // sin esto, una etiqueta nueva tardaría hasta el TTL en aparecer.
        CatalogCacheService::invalidateTags();

        return response()->json(['success' => true, 'tags' => $tags->all()]);
    }

    public function showFull(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load(['customer', 'conversation', 'status', 'category', 'assignee', 'group', 'slaPolicy', 'items.user', 'items.author', 'watchers', 'aiSuggestedCategory']);
        $ticket->load(['followups' => fn ($q) => $q->where('is_sent', false)->with('user')]);

        $sidebarQuery = Ticket::query()
            ->with(['customer', 'status', 'category'])
            ->latest();

        if ($request->has('status') && $request->status !== 'all') {
            $sidebarQuery->where('status_id', $request->status);
        }

        $tickets = $sidebarQuery->paginate(20);

        $statuses = CatalogCacheService::statuses();
        $categories = CatalogCacheService::categories();
        $groups = CatalogCacheService::groups();

        $userId = auth()->id();
        $itemIds = $ticket->items->pluck('id');

        if ($itemIds->isNotEmpty()) {
            $alreadyRead = TicketRead::where('user_id', $userId)
                ->whereIn('ticket_item_id', $itemIds)
                ->pluck('ticket_item_id')
                ->all();

            $toInsert = $itemIds
                ->diff($alreadyRead)
                ->map(fn ($id) => [
                    'ticket_item_id' => $id,
                    'user_id' => $userId,
                    'read_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

            if (! empty($toInsert)) {
                TicketRead::insert($toInsert);
            }
        }

        $agents = CatalogCacheService::agents();

        $mentionableUsers = $agents
            ->take(50)
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->firstname.' '.$u->lastname),
                'email' => $u->email,
            ])->values();

        $history = $ticket->history()->latest()->limit(50)->get();
        $ticketMails = $ticket->mails()->latest()->limit(30)->get();
        $cannedReplies = TicketCannedReply::where('is_active', true)
            ->where(fn ($q) => $q->where('is_global', true)->orWhere('user_id', $userId))
            ->orderBy('title')
            ->get(['id', 'title', 'content', 'html_body', 'short_code']);

        // Para el botón "Bloquear remitente" del panel de acciones: si el email
        // del cliente ya está cubierto por una regla (exacta o por dominio), la
        // vista muestra el aviso en vez del botón.
        $blacklistMatch = $ticket->customer?->email
            ? TicketEmailBlacklist::matches($ticket->customer->email)
            : null;

        // Revisión de calidad, si el muestreo alcanzó a este ticket. Se
        // resuelve aquí y no en la vista para no dejar una consulta en el
        // Blade; con la función apagada ni siquiera se pregunta.
        $qualityReview = config('helpdesktickets.quality_review.enabled', false)
            ? TicketReview::query()->where('ticket_id', $ticket->id)->first()
            : null;

        return view('helpdesktickets::managers.tickets.show', [
            'qualityReview' => $qualityReview,
            'ticket' => $ticket,
            'tickets' => $tickets,
            'statuses' => $statuses,
            'categories' => $categories,
            'groups' => $groups,
            // La vista lo recorre para el selector de participantes; sin el, show()
            // reventaba con "Undefined variable $agents" (500 en el detalle del ticket).
            'agents' => $agents,
            'mentionableUsers' => $mentionableUsers,
            'history' => $history,
            'ticketMails' => $ticketMails,
            'cannedReplies' => $cannedReplies,
            'blacklistMatch' => $blacklistMatch,
        ]);
    }

    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $categories = CatalogCacheService::categories();
        $statuses = CatalogCacheService::statuses();
        $slaPolicies = TicketSlaPolicy::active()->get();
        $groups = CatalogCacheService::groups();
        $agents = CatalogCacheService::agents();

        return view('helpdesktickets::managers.tickets.edit', [
            'ticket' => $ticket,
            'categories' => $categories,
            'statuses' => $statuses,
            'slaPolicies' => $slaPolicies,
            'groups' => $groups,
            'agents' => $agents,
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        // Defensa en profundidad: el resto del CRUD (show/edit/destroy/
        // restore) llama authorize() explícitamente contra la instancia; este
        // método dependía solo de UpdateTicketRequest::authorize() (permiso
        // estático), sin backstop si algún día se llama applyChanges() desde
        // otra ruta que no pase por ese FormRequest concreto.
        $this->authorize('update', $ticket);

        $this->ticketUpdateService->applyChanges($ticket, $request->getModifiableFields(), auth()->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesktickets::helpdesktickets.messages.ticket_updated'),
                'ticket' => $ticket->fresh(['customer', 'status', 'assignee']),
            ]);
        }

        // edit.blade.php (form #ticketForm, sin interceptar por JS) solo se
        // llega desde la ficha completa — se vuelve ahí, no al listado.
        return redirect()
            ->route('manager.helpdesk.tickets.show-full', $ticket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_updated'));
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return redirect()
            ->route('manager.helpdesk.tickets.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $ticket = Ticket::withTrashed()->findOrFail($id);
        $this->authorize('restore', $ticket);
        $ticket->restore();

        return redirect()->route('manager.helpdesk.tickets.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_restored'));
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $ticket = Ticket::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $ticket);
        $ticket->forceDelete();

        return redirect()->route('manager.helpdesk.tickets.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_permanently_deleted'));
    }
}

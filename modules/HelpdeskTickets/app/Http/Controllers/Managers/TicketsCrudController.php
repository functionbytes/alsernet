<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Helpdesk\Filters\TicketFilter;
use Modules\Helpdesk\Models\Company;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Http\Controllers\FeedbackController;
use Modules\HelpdeskTickets\Http\Requests\StoreTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\UpdateTicketRequest;
use Modules\HelpdeskTickets\Mail\TicketSatisfactionSurveyMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketRead;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Models\TicketView;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Modules\HelpdeskTickets\Services\HelpdeskTicketBridgeService;
use Modules\HelpdeskTickets\Services\OpsHealthService;
use Modules\HelpdeskTickets\Services\TicketMailAiSummaryService;
use Modules\HelpdeskTickets\Services\TicketUpdateService;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;
use Nwidart\Modules\Facades\Module;

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
            ->with(['customer', 'status', 'category', 'assignee'])
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
        $availableTags = Ticket::query()->whereNotNull('tags')->pluck('tags')
            ->flatten()->filter()->unique()->sort()->values();
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
        $rows = Ticket::query()
            ->notSnoozed()
            ->with('status:id,slug,name,is_open')
            ->select(['id', 'assignee_id', 'priority', 'status_id', 'sla_resolution_breached', 'sla_first_response_breached', 'sla_resolution_due_at'])
            ->get();

        $counts = ['open' => 0, 'urgent' => 0, 'mine' => 0, 'unassigned' => 0, 'pending' => 0, 'resolved' => 0, 'closed' => 0, 'sla_risk' => 0, 'all' => $rows->count()];

        foreach ($rows as $t) {
            $slug = $t->statusSlug();
            if ($slug === 'open' || $slug === 'progress') {
                $counts['open']++;
            }
            if ($t->priority === 'urgent' || $t->sla_resolution_breached || $t->sla_first_response_breached) {
                $counts['urgent']++;
            }
            if ($userId && $t->assignee_id === $userId) {
                $counts['mine']++;
            }
            if (! $t->assignee_id) {
                $counts['unassigned']++;
            }
            if ($slug === 'pending') {
                $counts['pending']++;
            }
            if ($slug === 'resolved') {
                $counts['resolved']++;
            }
            if ($slug === 'closed') {
                $counts['closed']++;
            }
            // Distinto de "urgent": solo SLA en riesgo/vencido, sin importar
            // prioridad — el mockup lo trae como chip de vista aparte.
            if (in_array($t->slaRowKind(), ['warn', 'breach'], true)) {
                $counts['sla_risk']++;
            }
        }

        return $counts;
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

        return view('helpdesktickets::managers.tickets.create', [
            'customer' => $customer,
            'customers' => $customers,
            'categories' => $categories,
            'statuses' => $statuses,
            'defaultStatus' => $defaultStatus,
            'slaPolicies' => $slaPolicies,
            'groups' => $groups,
            'agents' => $agents,
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

            if ($request->hasFile('attachments')) {
                $attachmentPaths = [];
                foreach ($request->file('attachments') as $file) {
                    $attachmentPaths[] = $file->store(
                        'helpdesk/tickets/'.$ticket->id,
                        config('helpdesk.attachments.disk', 'local')
                    );
                }

                if (! empty($data['description'])) {
                    $ticket->items()->create([
                        'type' => 'message',
                        'user_id' => auth()->id(),
                        'body' => $data['description'],
                        'attachment_urls' => $attachmentPaths,
                        'is_internal' => false,
                    ]);
                }
            } elseif (! empty($data['description'])) {
                $ticket->items()->create([
                    'type' => 'message',
                    'user_id' => auth()->id(),
                    'body' => $data['description'],
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
     * Resumen IA del ticket (mismo servicio y mismo criterio de "sin API
     * key configurada → summary null, nunca inventado" que ya usa
     * TicketMailsController::summary() por correo individual — aquí es a
     * nivel de ticket completo, para el banner del detalle nuevo).
     */
    public function summary(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        if (! class_exists(AgentLlmService::class)) {
            return response()->json(['success' => true, 'summary' => null]);
        }

        return response()->json([
            'success' => true,
            'summary' => app(TicketMailAiSummaryService::class)->summarize($ticket),
        ]);
    }

    /**
     * Snapshot de salud operativa (pill "Cola") — reusa OpsHealthService tal
     * cual (ya alimenta el dashboard de reports y el comando programado
     * helpdesk:ops-metrics); aquí solo se expone de forma ligera para el
     * modal del listado, sin duplicar ninguna de las sondas.
     */
    public function ops(): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        return response()->json(['success' => true, 'snapshot' => app(OpsHealthService::class)->cached()]);
    }

    /**
     * Carga real por agente (pill "Carga") — AssignmentService::
     * getAvailableAgents()/getAgentWorkload() ya existían pero sin ningún
     * punto de entrada HTTP. Sin "capacidad" ni "% ocupación" por agente:
     * no hay ninguna columna de capacidad máxima configurada, así que
     * mostrarla sería inventar un dato.
     */
    public function workload(): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $service = app(AssignmentService::class);
        $agents = $service->getAvailableAgents();

        return response()->json([
            'success' => true,
            'agents' => $agents->map(fn ($agent) => [
                'id' => $agent->id,
                'name' => trim($agent->firstname.' '.$agent->lastname),
                'open_tickets' => $service->getAgentWorkload($agent->id),
            ])->sortByDesc('open_tickets')->values()->all(),
            'unassigned_count' => Ticket::query()->whereNull('assignee_id')->notSnoozed()->count(),
        ]);
    }

    /**
     * "Repartir sin asignar" — aplica AssignmentService::autoAssignByWorkload()
     * (ya usado por la asignación automática existente) a cada ticket sin
     * asignar visible en cola; un fallo puntual en un ticket no aborta el
     * resto. Requiere permiso de gestión (no solo lectura, como ops()/workload()).
     */
    public function distributeUnassigned(): JsonResponse
    {
        abort_unless(auth()->user()?->can('helpdesk.tickets.update'), 403);

        $service = app(AssignmentService::class);
        $assigned = 0;

        Ticket::query()->whereNull('assignee_id')->notSnoozed()->limit(50)->get()->each(function (Ticket $ticket) use ($service, &$assigned) {
            if ($service->autoAssignByWorkload($ticket)) {
                $assigned++;
            }
        });

        return response()->json(['success' => true, 'message' => "{$assigned} ticket(s) repartido(s).", 'assigned' => $assigned]);
    }

    /**
     * Reenvía la encuesta CSAT — misma plantilla/Mailable/enlace firmado que
     * UpdateTicketOnClose ya usa automáticamente al cerrar (helpdesk_tickets.
     * satisfaction_survey), extraído aquí como acción manual del agente para
     * el caso "el cliente no la vio" o "se cerró sin cliente con email en
     * ese momento". Mismas condiciones: ticket cerrado, cliente con email,
     * sin valorar todavía.
     */
    public function sendCsatSurvey(Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        abort_unless($ticket->closed_at, 422, 'El ticket debe estar cerrado para enviar la encuesta.');
        abort_if($ticket->rated_at, 422, 'Este ticket ya tiene una valoración.');

        $customer = $ticket->customer;
        abort_unless($customer?->email, 422, 'El cliente no tiene email registrado.');

        $ratingButtons = '';
        for ($i = 1; $i <= 5; $i++) {
            $rateUrl = URL::signedRoute('portal.tickets.rate.email', [
                'ticketNumber' => $ticket->ticket_number,
                'rating' => $i,
            ]);
            $ratingButtons .= '<a href="'.e($rateUrl).'" style="display: inline-block; margin: 0 6px; padding: 12px 20px; background: #f9f9f9; border: 2px solid #ddd; border-radius: 50%; font-size: 22px; text-decoration: none; color: #333; font-weight: bold;">'.$i.'</a>';
        }

        [$subject, $content] = TicketMailRenderer::render(
            'helpdesk_tickets.satisfaction_survey',
            [
                'TICKET_NUMBER' => $ticket->ticket_number,
                'TICKET_SUBJECT' => e($ticket->subject),
                'CLOSED_AT' => $ticket->closed_at?->format('d/m/Y H:i') ?? '',
                'RATING_BUTTONS' => $ratingButtons,
                'FEEDBACK_URL' => FeedbackController::signedShowUrl($ticket),
            ],
            'Cuéntanos tu experiencia — Ticket #'.$ticket->ticket_number,
        );

        Mail::to($customer->email)->queue(new TicketSatisfactionSurveyMail($ticket, $subject, $content));

        return response()->json(['success' => true, 'message' => 'Encuesta de satisfacción enviada.']);
    }

    public function data(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load(['items' => fn ($q) => $q->orderBy('created_at'), 'items.user', 'items.author', 'followups', 'aiSuggestedCategory', 'watchers.user']);

        $thread = $ticket->items->map(fn ($item) => [
            'id' => $item->id,
            'type' => $item->type,
            'is_internal' => (bool) $item->is_internal,
            'sender_name' => $item->sender_name,
            'from_agent' => $item->isFromAgent(),
            'body' => $item->content,
            'attachment_count' => $item->attachment_count,
            'created_at' => $item->created_at?->toIso8601String(),
            'created_at_human' => $item->created_at?->diffForHumans(),
        ])->values();

        // OJO: este proyecto tiene una copia vendored antigua de
        // spatie/laravel-activitylog dentro de modules/Activity/vendor/...
        // que el autoload PSR-4 resuelve ANTES que vendor/spatie/... (mismo
        // classmap-split ya documentado en el proyecto). Esa copia antigua
        // define activities() directamente (no activitiesAsSubject(), que
        // es de la copia nueva en vendor/ raíz y aquí nunca se carga) —
        // confirmado en runtime: activitiesAsSubject() lanzaba
        // BadMethodCallException real.
        $activity = $ticket->activities()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'description' => $a->description,
                'causer' => $a->causer?->name,
                'created_at_human' => $a->created_at?->diffForHumans(),
            ])->values();

        $attachmentsDisk = config('helpdesk.attachments.disk', 'local');
        $files = $ticket->items
            ->filter(fn ($item) => $item->hasAttachments())
            ->flatMap(fn ($item) => collect($item->attachment_urls)->values()->map(fn ($path, $index) => [
                'name' => basename((string) $path),
                'item_id' => $item->id,
                'created_at_human' => $item->created_at?->diffForHumans(),
                // Descarga real (TicketAttachmentDownloadController) — el
                // índice es la posición dentro de attachment_urls del item,
                // que es como la ruta lo resuelve.
                'url_download' => route('manager.helpdesk.tickets.attachments.download', [$ticket, $item->id, $index]),
                'size' => Storage::disk($attachmentsDisk)->exists((string) $path)
                    ? Storage::disk($attachmentsDisk)->size((string) $path)
                    : null,
            ]))
            ->values();

        $allMails = $ticket->mails()->latest()->limit(50)->get();
        $lastMail = $allMails->first();

        $notes = TicketNote::where('ticket_id', $ticket->id)
            ->with('user')
            ->orderByDesc('is_pinned')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (TicketNote $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'color' => $n->color,
                'is_pinned' => (bool) $n->is_pinned,
                'author_name' => $n->user ? trim($n->user->firstname.' '.$n->user->lastname) : 'Agente',
                'created_at_human' => $n->created_at?->diffForHumans(),
            ])->values();

        return response()->json([
            'thread' => $thread,
            'activity' => $activity,
            'files' => $files,
            'mail' => $lastMail ? [
                'subject' => $lastMail->subject,
                'to' => $lastMail->to,
                'status' => $lastMail->status,
                'body_html' => $lastMail->safeBodyHtml(),
                'created_at_human' => $lastMail->created_at?->diffForHumans(),
                // Reusa el mismo endpoint que ya existe en la bandeja de
                // emails (TicketMailsController::resend()) — sin duplicar
                // lógica de reenvío.
                'url_resend' => route('manager.helpdesk.tickets.emails.resend', $lastMail),
                // Aperturas reales (EmailLogOpen, mismo cruce por message_id
                // que traceFor()) — "reintentos" del mockup se omite: no hay
                // columna de intentos en EmailLog, no se inventa.
                ...$this->mailOpensSummary($lastMail),
            ] : null,
            'trace' => $lastMail ? $this->traceFor($lastMail) : [],
            // Lista completa (modal "Correos del ticket") — antes solo se
            // veía el último; el resto obligaba a salir a la bandeja global.
            'mails' => $allMails->map(fn ($m) => [
                'id' => $m->id,
                'subject' => $m->subject,
                'to' => $m->to,
                'direction' => $m->direction,
                'status' => $m->status,
                'created_at_human' => $m->created_at?->diffForHumans(),
            ])->values()->all(),
            'customer' => $this->mapCustomerDetail($ticket->customer),
            'form' => $this->formDataFor($ticket),
            'notes' => $notes,
            'related' => $this->relatedTicketsFor($ticket),
            'side_conversations' => $this->sideConversationsFor($ticket),
            // Seguimientos/recordatorios reales (TicketFollowupsController,
            // ya con backend+comando programado helpdesk:send-due-ticket-followups
            // — solo faltaba exponerlos en esta pantalla; la ficha antigua
            // show.blade.php ya los muestra).
            'followups' => $ticket->followups->map(fn ($f) => [
                'id' => $f->id,
                'scheduled_at' => $f->scheduled_at?->toIso8601String(),
                'scheduled_at_human' => $f->scheduled_at?->format('d/m/Y H:i'),
                'note' => $f->note,
            ])->values()->all(),
            // Sugerencias de IA ya calculadas (TicketAiService) pero sin
            // punto de aplicación en esta pantalla hasta ahora — mismo
            // criterio que show.blade.php: si no hay sugerencia real, se
            // omite el bloque entero (nunca se muestra un % de confianza,
            // el backend no lo guarda).
            'ai_suggestion' => ($ticket->aiSuggestedCategory || $ticket->ai_suggested_priority) ? [
                'category' => $ticket->aiSuggestedCategory ? [
                    'id' => $ticket->aiSuggestedCategory->id,
                    'name' => $ticket->aiSuggestedCategory->name,
                ] : null,
                'priority' => $ticket->ai_suggested_priority,
            ] : null,
            // Seguidores reales (TicketWatcher) — antes solo se podía
            // auto-seguirse, sin lista visible en esta pantalla.
            'watchers' => $ticket->watchers->map(fn ($w) => [
                'user_id' => $w->user_id,
                'name' => $w->user ? trim($w->user->firstname.' '.$w->user->lastname) : ('Usuario #'.$w->user_id),
                'is_me' => $w->user_id === auth()->id(),
            ])->values()->all(),
            // CSAT — mismo campo que ya rellena FeedbackController::submit()
            // al valorar. can_resend exige lo mismo que sendCsatSurvey():
            // cerrado, cliente con email, sin valorar todavía.
            'csat' => [
                'rating' => $ticket->rating,
                'comment' => $ticket->rating_comment,
                'reason' => $ticket->rating_reason,
                'rated_at_human' => $ticket->rated_at?->diffForHumans(),
                'can_resend' => (bool) ($ticket->closed_at && ! $ticket->rated_at && $ticket->customer?->email),
            ],
        ]);
    }

    /**
     * "Conversación paralela" — mismo contrato que
     * TicketSideConversationsController::index(), resumido para el panel
     * lateral (Correo). No se duplica el endpoint completo: crear/añadir
     * mensaje siguen pasando por sus rutas propias.
     */
    private function sideConversationsFor(Ticket $ticket): array
    {
        return $ticket->sideConversations()
            ->with(['messages', 'participantUser:id,firstname,lastname'])
            ->get()
            ->map(fn ($side) => [
                'id' => $side->id,
                'subject' => $side->subject,
                'participant_type' => $side->participant_type,
                'participant_email' => $side->participant_email,
                'participant' => $side->participantUser?->full_name,
                'status' => $side->status,
                'message_count' => $side->messages->count(),
            ])->values()->all();
    }

    /**
     * Cliente + integraciones — mismo patrón que
     * TicketMailsController::mapCustomer()/contactStats(): reusa Contactos
     * 360 (HelpdeskContacts) si está instalado y activo, dependencia
     * opcional en runtime nunca declarada en module.json. Sin ese módulo,
     * estos datos simplemente no se muestran.
     */
    private function mapCustomerDetail(?Customer $customer): ?array
    {
        if (! $customer) {
            return null;
        }

        $company = $customer->company_id ? Company::find($customer->company_id) : null;
        $stats = $this->contactStats($customer);

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'company' => $company?->name,
            'customer_since_year' => $customer->created_at?->format('Y'),
            'language' => $customer->language,
            'tickets_count' => $stats['tickets_count'] ?? null,
            'avg_csat' => $stats['avg_csat'] ?? null,
            'integrations' => $stats['integrations'] ?? [],
            'url_c360' => Route::has('contacts.show') ? route('contacts.show', $customer->id) : null,
        ];
    }

    /**
     * @return array{tickets_count?: int, avg_csat?: float, integrations?: array<int, array<string, mixed>>}
     */
    private function contactStats(Customer $customer): array
    {
        if (! Module::find('HelpdeskContacts')?->isEnabled() || ! class_exists(ContactAggregatorService::class)) {
            return [];
        }

        try {
            $resumen = app(ContactAggregatorService::class)->resumen($customer);
        } catch (\Throwable) {
            return [];
        }

        return [
            'tickets_count' => $resumen['stats']['ticketsCount'] ?? null,
            'avg_csat' => $resumen['stats']['avgCsat'] ?? null,
            'integrations' => collect($resumen['integrations'] ?? [])->filter(fn (array $i) => $i['connected'])->values()->all(),
        ];
    }

    /**
     * Datos del formulario de origen — solo si el ticket viene de un canal
     * de formulario y tiene custom_fields capturados; si no, null (el JS
     * muestra el estado vacío honesto en vez de inventar datos).
     */
    private function formDataFor(Ticket $ticket): ?array
    {
        if (empty($ticket->custom_fields) || ! in_array($ticket->sourceSlug(), ['formulario', 'web_form'], true)) {
            return null;
        }

        return [
            'fields' => $ticket->custom_fields,
        ];
    }

    /**
     * Otros tickets del mismo cliente — reusa
     * HelpdeskTicketBridgeService::getCustomerTickets() (ya usado por
     * Contactos 360 y por la bandeja de emails), excluyendo el actual.
     */
    private function relatedTicketsFor(Ticket $ticket): array
    {
        // Dos fuentes distintas, fusionadas: los enlaces EXPLÍCITOS
        // (TicketLink, creados vía "Vincular ticket" en ambas direcciones —
        // se conserva su id/link_type propios para poder desvincular, cosa
        // que un ticket "relacionado" solo por ser del mismo cliente no
        // tiene) y los tickets del MISMO cliente (sugerencia automática).
        // unlinkTicket() solo borra filas donde ticket_id = $ticket->id, así
        // que solo el lado "propietario" del enlace (links(), no
        // linkedBy()) puede desvincularse desde aquí — desvincular desde el
        // otro extremo requeriría abrir el ticket contrario.
        $ownLinks = $ticket->links()->with('linkedTicket.status')->get()
            ->map(fn ($l) => ['link_id' => $l->id, 'link_type' => $l->link_type, 'ticket' => $l->linkedTicket, 'unlinkable' => true]);
        $reverseLinks = $ticket->linkedBy()->with('ticket.status')->get()
            ->map(fn ($l) => ['link_id' => $l->id, 'link_type' => $l->link_type, 'ticket' => $l->ticket, 'unlinkable' => false]);

        $explicitLinks = $ownLinks->concat($reverseLinks)->filter(fn ($row) => $row['ticket'] !== null);

        $customerRelated = $ticket->customer
            ? app(HelpdeskTicketBridgeService::class)->getCustomerTickets($ticket->customer, 6)
                ->map(fn (Ticket $t) => ['link_id' => null, 'link_type' => null, 'ticket' => $t, 'unlinkable' => false])
            : collect();

        return $explicitLinks->concat($customerRelated)
            ->unique(fn ($row) => $row['ticket']->id)
            ->reject(fn ($row) => $row['ticket']->id === $ticket->id)
            ->take(8)
            ->map(fn ($row) => [
                'id' => $row['ticket']->id,
                'ticket_number' => $row['ticket']->ticket_number,
                'subject' => $row['ticket']->subject,
                'status_name' => $row['ticket']->status?->name,
                'status_slug' => $row['ticket']->statusSlug(),
                'link_type' => $row['link_type'],
                'url_unlink' => $row['unlinkable'] ? route('manager.helpdesk.tickets.unlink', [$ticket, $row['link_id']]) : null,
            ])->values()->all();
    }

    /**
     * Trazabilidad de entrega del último correo del ticket — mismo cruce
     * EmailLog/EmailLogOpen por message_id (trim de '<>') ya construido para
     * la bandeja de emails, ver TicketMailsController::traceFor().
     */
    /**
     * @return array{opens_count: int, last_opened_human: ?string}
     */
    private function mailOpensSummary(TicketMail $mail): array
    {
        if (! $mail->message_id) {
            return ['opens_count' => 0, 'last_opened_human' => null];
        }

        $log = EmailLog::with('opens')->where('message_id', trim($mail->message_id, '<>'))->first();
        $lastOpen = $log?->opens->sortByDesc('opened_at')->first();

        return [
            'opens_count' => $log?->opens->count() ?? 0,
            'last_opened_human' => $lastOpen?->opened_at?->diffForHumans(),
        ];
    }

    private function traceFor(TicketMail $mail): array
    {
        if (! $mail->message_id) {
            return [];
        }

        $log = EmailLog::with('opens')->where('message_id', trim($mail->message_id, '<>'))->first();

        if (! $log) {
            return [];
        }

        $events = [
            ['type' => 'queued', 'label' => 'Encolado en emails', 'at' => $log->created_at?->toIso8601String()],
        ];

        if ($log->sent_at) {
            $events[] = ['type' => 'sent', 'label' => 'Aceptado por el servidor de correo', 'at' => $log->sent_at->toIso8601String()];
        }
        if ($log->bounced_at) {
            $events[] = ['type' => 'bounced', 'label' => 'Rebotado', 'at' => $log->bounced_at->toIso8601String()];
        }
        if ($log->failed_at) {
            $events[] = ['type' => 'failed', 'label' => 'Fallido', 'at' => $log->failed_at->toIso8601String()];
        }
        foreach ($log->opens as $open) {
            $events[] = ['type' => 'opened', 'label' => 'Abierto por el destinatario', 'at' => $open->opened_at?->toIso8601String()];
        }

        return $events;
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

        return view('helpdesktickets::managers.tickets.show', [
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

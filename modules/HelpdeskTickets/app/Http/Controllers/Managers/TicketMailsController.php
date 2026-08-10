<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Models\Company;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskTickets\Http\Requests\Managers\BulkTicketMailRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\ComposeTicketMailRequest;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Modules\HelpdeskTickets\Services\HelpdeskTicketBridgeService;
use Modules\HelpdeskTickets\Services\TicketMailAiSummaryService;
use Modules\HelpdeskTickets\Services\TicketMailDispatcher;
use Modules\HelpdeskTickets\Services\TicketVariableInterpolator;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bandeja "Emails enviados" — vista global de helpdesk_ticket_mails (todos
 * los tickets), a diferencia del widget de hasta 30 filas que ya existía
 * dentro de la ficha de un ticket concreto (TicketsCrudController::showFull).
 */
class TicketMailsController extends Controller
{
    public function __construct(
        private readonly TicketMailDispatcher $dispatcher,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', TicketMail::class);

        abort_if(! helpdesk_tickets_enabled(), 404);

        $mails = $this->filteredQuery($request)->paginate(50)->appends($request->query());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $mails->getCollection()->map(fn (TicketMail $m) => $m->toListRow())->values(),
                'meta' => [
                    'current_page' => $mails->currentPage(),
                    'last_page' => $mails->lastPage(),
                    'total' => $mails->total(),
                ],
                // Los KPIs de arriba (enviados/rebotados/tasa/programados) se
                // hidratan en el primer render SSR pero refetch() vuelve a
                // pedir esta misma ruta por AJAX en cada envío/reenvío/acción
                // masiva — sin esto se quedaban congelados con los valores
                // del primer GET (bug real encontrado en QA manual).
                'stats' => $this->stats(),
            ]);
        }

        return view('helpdesktickets::managers.emails.index', [
            'mails' => $mails,
            'categories' => CatalogCacheService::categories(),
            'agents' => CatalogCacheService::agents(),
            'stats' => $this->stats(),
            'filters' => $request->only(['view', 'search', 'origin', 'category', 'agent', 'from', 'to']),
        ]);
    }

    public function data(TicketMail $mail): JsonResponse
    {
        $this->authorize('view', $mail);

        $mail->load(['ticket.customer', 'ticket.status', 'ticket.category', 'ticket.assignee', 'user', 'category']);

        $thread = TicketMail::where('ticket_id', $mail->ticket_id)->oldest()->get();

        return response()->json([
            'success' => true,
            'data' => array_merge($mail->toListRow(), [
                'from' => $mail->from,
                'cc' => $mail->cc,
                'bcc' => $mail->bcc,
                'message_id' => $mail->message_id,
                'in_reply_to' => $mail->in_reply_to,
                'body_html' => $mail->safeBodyHtml(),
                'body_text' => $mail->body_text,
                'attachments' => $mail->attachments ?? [],
                'delivery_error' => $mail->delivery_error,
                'thread' => $thread->map(fn (TicketMail $m) => $m->toListRow())->values(),
                'ticket' => $this->mapTicket($mail->ticket),
                'trace' => $this->traceFor($mail),
                'activity' => $this->activityFor($mail),
                'related' => $this->relatedTickets($mail),
            ]),
        ]);
    }

    /**
     * Añade/quita una etiqueta de un email concreto (JSON array simple en
     * TicketMail.tags — no hay catálogo de tags como el de conversaciones,
     * suficiente para lo que pide el panel lateral de esta pantalla).
     */
    public function updateTags(Request $request, TicketMail $mail): JsonResponse
    {
        $this->authorize('update', $mail);

        $validated = $request->validate([
            'add' => ['nullable', 'string', 'max:50'],
            'remove' => ['nullable', 'string', 'max:50'],
        ]);

        $tags = collect($mail->tags ?? []);

        if (! empty($validated['add'])) {
            $tags = $tags->push(trim($validated['add']))->filter()->unique()->values();
        }

        if (! empty($validated['remove'])) {
            $tags = $tags->reject(fn ($t) => $t === $validated['remove'])->values();
        }

        $mail->update(['tags' => $tags->all()]);

        return response()->json(['success' => true, 'tags' => $tags->all()]);
    }

    /**
     * Traducción bajo demanda del cuerpo del mensaje — reusa el traductor ya
     * cacheado de HelpdeskTranslate (mismo proveedor/caché que la traducción
     * automática de conversaciones), no una llamada a DeepL propia.
     */
    public function translate(Request $request, TicketMail $mail): JsonResponse
    {
        $this->authorize('view', $mail);

        if (! class_exists(CachedTranslator::class)) {
            return response()->json(['success' => false, 'message' => 'La traducción no está disponible.'], 422);
        }

        $target = $request->string('target', 'es')->toString();
        $text = strip_tags($mail->body_html ?: $mail->body_text ?: '');

        if (trim($text) === '') {
            return response()->json(['success' => false, 'message' => 'Este email no tiene contenido que traducir.'], 422);
        }

        $translator = app(CachedTranslator::class);
        $translated = $translator->translate($text, $target, null, 'manual');

        if ($translated === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo traducir el mensaje (proveedor no disponible o cuota agotada).'], 422);
        }

        return response()->json(['success' => true, 'translated' => $translated, 'target' => $target]);
    }

    /**
     * Resumen IA bajo demanda del ticket del email seleccionado — banner
     * "Resumen IA" del mockup. Vacío/oculto si no hay LLM configurado (nunca
     * datos inventados).
     */
    public function summary(TicketMail $mail): JsonResponse
    {
        $this->authorize('view', $mail);

        $ticket = $mail->ticket;

        if (! $ticket || ! class_exists(AgentLlmService::class)) {
            return response()->json(['success' => true, 'summary' => null]);
        }

        $summary = app(TicketMailAiSummaryService::class)->summarize($ticket);

        return response()->json(['success' => true, 'summary' => $summary]);
    }

    /**
     * Plantillas para el compose modal — reusa el catálogo de Macro ya
     * existente (mismo usado por el command palette de tickets), filtrado a
     * las que tienen una acción 'reply' (las únicas con texto insertable en
     * un email). El texto se interpola con las variables del ticket dado
     * (mismo TicketVariableInterpolator que ya usa MacroExecutor).
     */
    public function templates(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TicketMail::class);

        $ticket = $request->filled('ticket_id') ? Ticket::find($request->integer('ticket_id')) : null;
        $interpolator = new TicketVariableInterpolator;

        $templates = Macro::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_shared', true)->orWhere('user_id', auth()->id());
            })
            ->get(['id', 'name', 'description', 'actions'])
            ->map(function ($macro) use ($ticket, $interpolator) {
                $reply = collect($macro->actions)->firstWhere('type', 'reply');

                if (! $reply) {
                    return null;
                }

                $body = (string) ($reply['body'] ?? '');

                return [
                    'id' => $macro->id,
                    'name' => $macro->name,
                    'description' => $macro->description,
                    'body' => $ticket ? $interpolator->interpolate($body, $ticket) : $body,
                ];
            })
            ->filter()
            ->values();

        return response()->json(['success' => true, 'templates' => $templates]);
    }

    public function store(ComposeTicketMailRequest $request): JsonResponse
    {
        $ticket = Ticket::findOrFail($request->integer('ticket_id'));

        $this->authorize('view', $ticket);
        $this->authorize('create', TicketMail::class);

        $validated = $request->validated();

        [$attachmentFiles, $attachmentMeta] = $this->storeAttachments($request, $ticket);

        $scheduledAt = $validated['scheduled_at'] ?? null;

        $mail = TicketMail::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'category_id' => $validated['category_id'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'direction' => 'outbound',
            'is_internal' => $validated['is_internal'] ?? false,
            'from' => config('mail.from.address'),
            'to' => $validated['to'],
            'cc' => isset($validated['cc']) ? implode(',', $validated['cc']) : null,
            'bcc' => isset($validated['bcc']) ? implode(',', $validated['bcc']) : null,
            'subject' => $validated['subject'],
            'body_html' => $validated['body'],
            'body_text' => strip_tags($validated['body']),
            'attachments' => $attachmentMeta ?: null,
            'status' => $scheduledAt ? 'scheduled' : 'pending',
            'scheduled_at' => $scheduledAt,
        ]);

        if ($scheduledAt) {
            return response()->json([
                'success' => true,
                'message' => 'Email programado correctamente.',
                'data' => $mail->fresh()->toListRow(),
            ], 201);
        }

        $this->dispatcher->send($mail, $ticket, $validated['cc'] ?? [], $validated['bcc'] ?? [], $attachmentFiles);

        return response()->json([
            'success' => true,
            'message' => 'Email enviado correctamente.',
            'data' => $mail->fresh()->toListRow(),
        ], 201);
    }

    public function resend(Request $request, TicketMail $mail): JsonResponse
    {
        $this->authorize('resend', $mail);

        $ticket = $mail->ticket;
        abort_if(! $ticket, 404);

        $to = $request->filled('to') ? $request->string('to')->toString() : $mail->to;

        $newMail = $this->createResendCopy($mail, $ticket, $to);

        $this->dispatcher->send($newMail, $ticket, [], [], $this->dispatcher->resendableAttachments($mail));

        return response()->json([
            'success' => true,
            'message' => "Email reenviado a {$to}.",
            'data' => $newMail->fresh()->toListRow(),
        ]);
    }

    /**
     * Acciones masivas: reenviar o cancelar programados. Autorización por
     * email (igual que BulkTicketsController): los que el usuario no puede
     * actuar se omiten en vez de abortar toda la operación.
     */
    public function bulk(BulkTicketMailRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];

        $mails = TicketMail::whereIn('id', $validated['mail_ids'])->get();

        $ability = $action === 'resend' ? 'resend' : 'delete';
        [$authorized, $skipped] = $mails->partition(
            fn (TicketMail $m) => $request->user()->can($ability, $m)
        );

        if ($authorized->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para actuar sobre los emails seleccionados.',
                'skipped_ids' => $skipped->pluck('id')->values(),
            ], 403);
        }

        $count = match ($action) {
            'resend' => $this->bulkResend($authorized),
            'cancel_scheduled' => $authorized->filter(fn (TicketMail $m) => $m->status === 'scheduled')
                ->each(fn (TicketMail $m) => $m->delete())
                ->count(),
        };

        $message = "{$count} emails procesados.";
        if ($skipped->isNotEmpty()) {
            $message .= " {$skipped->count()} omitidos por falta de permiso.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'skipped_ids' => $skipped->pluck('id')->values(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', TicketMail::class);

        $query = $this->filteredQuery($request);

        if ($request->filled('ids')) {
            $query->whereIn('id', (array) $request->input('ids'));
        }

        $rows = $query->limit(5000)->cursor();
        $filename = 'emails-enviados-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Ticket', 'Asunto', 'Para', 'Estado', 'Agente', 'Categoría', 'Enviado', 'Programado']);

            foreach ($rows as $mail) {
                fputcsv($out, [
                    $mail->ticket?->ticket_number,
                    $mail->subject,
                    $mail->to,
                    $mail->status_label,
                    $mail->user ? trim("{$mail->user->firstname} {$mail->user->lastname}") : '',
                    $mail->category?->name,
                    $mail->sent_at?->format('Y-m-d H:i:s'),
                    $mail->scheduled_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(TicketMail $mail): JsonResponse
    {
        $this->authorize('delete', $mail);

        $mail->delete();

        return response()->json(['success' => true, 'message' => 'Email eliminado.']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function filteredQuery(Request $request): Builder
    {
        $query = TicketMail::query()
            ->with([
                'ticket:id,ticket_number,subject,customer_id,source,category_id,assignee_id',
                'ticket.customer:id,name,email',
                'user:id,firstname,lastname',
                'category:id,name',
            ])
            ->latest();

        match ($request->string('view', 'outbound')->toString()) {
            'scheduled' => $query->scheduled(),
            'bounced' => $query->bounced(),
            'failed' => $query->failed(),
            'inbound' => $query->inbound(),
            // Internos: avisos que nunca salen a un cliente (p.ej. escalados
            // a soporte-n2@alvarez.mx) — tab propio, no mezclado con "Enviados".
            'internal' => $query->outbound()->internal(),
            default => $query->outbound()->where('status', '!=', 'scheduled')->where('is_internal', false),
        };

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('subject', 'like', "%{$search}%")
                ->orWhere('to', 'like', "%{$search}%")
                ->orWhere('message_id', 'like', "%{$search}%")
                ->orWhereHas('ticket', fn ($t) => $t->where('ticket_number', 'like', "%{$search}%"))
            );
        }

        if ($request->filled('origin')) {
            $origin = $request->string('origin')->toString();
            $query->whereHas('ticket', fn ($t) => $t->where('source', $origin));
        }

        if ($request->filled('tag')) {
            $tag = $request->string('tag')->toString();
            $query->whereJsonContains('tags', $tag);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        if ($request->filled('agent')) {
            $query->where('user_id', $request->integer('agent'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from')->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        return $query;
    }

    /**
     * @return array{total: int, bounced: int, bounce_rate: float, scheduled: int, internal: int, opened_rate: float, avg_latency: ?float, queue_waiting: int}
     */
    private function stats(): array
    {
        $total = TicketMail::outbound()->where('is_internal', false)->count();
        $bounced = TicketMail::outbound()->where('is_internal', false)->bounced()->count();
        $scheduled = TicketMail::scheduled()->count();
        $internal = TicketMail::outbound()->internal()->count();

        // Aperturas y latencia salen de EmailLog (módulo HelpdeskEmailLog),
        // cruzado por message_id — TicketMail no trackea nada de eso, sería
        // duplicar una fuente de verdad que ya existe. TicketMail.message_id
        // se guarda con los ángulos <...> (formato de cabecera RFC 5322);
        // EmailLog.message_id se guarda SIN ellos (ver LogEmailQueued::
        // ensureMessageId()) — sin el trim() de abajo el whereIn() nunca
        // encuentra nada (bug real encontrado al probar el pixel en vivo).
        $messageIds = TicketMail::outbound()
            ->where('is_internal', false)
            ->whereNotNull('message_id')
            ->pluck('message_id')
            ->map(fn (string $id) => trim($id, '<>'));

        $logs = $messageIds->isEmpty()
            ? collect()
            : EmailLog::withCount('opens')
                ->whereIn('message_id', $messageIds)
                ->get(['id', 'message_id', 'created_at', 'sent_at']);

        $openedCount = $logs->filter(fn (EmailLog $l) => $l->opens_count > 0)->count();
        $openedRate = $logs->isNotEmpty() ? round($openedCount / $logs->count() * 100, 1) : 0.0;

        $latencies = $logs
            ->filter(fn (EmailLog $l) => $l->sent_at && $l->created_at)
            ->map(fn (EmailLog $l) => $l->created_at->diffInSeconds($l->sent_at));

        return [
            'total' => $total,
            'bounced' => $bounced,
            'bounce_rate' => $total > 0 ? round($bounced / $total * 100, 1) : 0.0,
            'scheduled' => $scheduled,
            'internal' => $internal,
            'opened_rate' => $openedRate,
            'avg_latency' => $latencies->isNotEmpty() ? round($latencies->avg(), 1) : null,
            'queue_waiting' => $this->queueWaiting(),
        ];
    }

    private function queueWaiting(): int
    {
        try {
            return Queue::connection()->size('emails');
        } catch (\Throwable) {
            // El tamaño de cola es un "nice to have" del header — un driver
            // que no lo soporte (p.ej. sync en tests) no debe tumbar la
            // pantalla completa.
            return 0;
        }
    }

    /**
     * Reconstruye la línea de tiempo de entrega para el tab "Trazabilidad"
     * cruzando EmailLog (encolado/aceptado/rebotado) + email_log_opens
     * (aperturas) por message_id. Solo se muestra lo que de verdad
     * capturamos — nada de datos de relay/DKIM que no tenemos.
     *
     * @return array<int, array{type: string, label: string, detail: ?string, at: ?string}>
     */
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
            [
                'type' => 'queued',
                'label' => 'Encolado en emails',
                'detail' => 'job SendQueuedMailable',
                'at' => $log->created_at?->toIso8601String(),
            ],
        ];

        if ($log->sent_at) {
            $events[] = [
                'type' => 'sent',
                'label' => 'Aceptado por el servidor de correo',
                'detail' => $log->created_at ? round($log->created_at->diffInSeconds($log->sent_at), 1).' s de latencia' : null,
                'at' => $log->sent_at->toIso8601String(),
            ];
        }

        if ($log->bounced_at) {
            $events[] = [
                'type' => 'bounced',
                'label' => 'Rebotado',
                'detail' => $log->error_message,
                'at' => $log->bounced_at->toIso8601String(),
            ];
        }

        if ($log->failed_at) {
            $events[] = [
                'type' => 'failed',
                'label' => 'Falló el envío',
                'detail' => $log->error_message,
                'at' => $log->failed_at->toIso8601String(),
            ];
        }

        $opens = $log->opens;
        if ($opens->isNotEmpty()) {
            $last = $opens->max('opened_at');
            $events[] = [
                'type' => 'opened',
                'label' => 'Abierto por el destinatario · '.$opens->count().' '.($opens->count() === 1 ? 'vez' : 'veces'),
                'detail' => 'última '.$last->format('H:i'),
                'at' => $opens->min('opened_at')?->toIso8601String(),
            ];
        }

        return $events;
    }

    /**
     * Tab "Actividad" — reusa el activity log que Ticket ya escribe (Spatie
     * Activitylog, ver Ticket::getActivitylogOptions()) en vez de duplicarlo
     * en TicketMail. No se inventa nada que el ticket no haya registrado ya.
     *
     * @return array<int, array{label: string, causer: ?string, at: ?string}>
     */
    private function activityFor(TicketMail $mail): array
    {
        if (! $mail->ticket) {
            return [];
        }

        return $mail->ticket->activities()
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($activity) => [
                'label' => $activity->description,
                'causer' => $activity->causer?->firstname
                    ? trim("{$activity->causer->firstname} {$activity->causer->lastname}")
                    : null,
                'at' => $activity->created_at?->toIso8601String(),
                'at_human' => $activity->created_at?->diffForHumans(),
            ])
            ->values()
            ->all();
    }

    /**
     * Tab "Relacionados" — otros tickets del mismo cliente, reusando
     * HelpdeskTicketBridgeService::getCustomerTickets() (ya usado hoy por
     * Contactos 360) en vez de reinventar la consulta.
     *
     * @return array<int, array{id: int, ticket_number: ?string, subject: string, status: ?string}>
     */
    private function relatedTickets(TicketMail $mail): array
    {
        $ticket = $mail->ticket;

        if (! $ticket || ! $ticket->customer) {
            return [];
        }

        return app(HelpdeskTicketBridgeService::class)
            ->getCustomerTickets($ticket->customer, 10)
            ->reject(fn (Ticket $t) => $t->id === $ticket->id)
            ->map(fn (Ticket $t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'status' => $t->status?->name,
                'url_full' => route('manager.helpdesk.tickets.show-full', $t),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapTicket(?Ticket $ticket): ?array
    {
        if (! $ticket) {
            return null;
        }

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'status' => $ticket->status?->name,
            'customer' => $this->mapCustomer($ticket->customer),
            'assignee' => $ticket->assignee ? trim("{$ticket->assignee->firstname} {$ticket->assignee->lastname}") : null,
            'category' => $ticket->category?->name,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'created_at_human' => $ticket->created_at?->format('Y-m-d H:i'),
            'updated_at_human' => $ticket->updated_at?->diffForHumans(),
            'tags' => $ticket->tags ?? [],
            'source' => $ticket->source,
            'sla' => $this->mapSla($ticket),
            'url_full' => route('manager.helpdesk.tickets.show-full', $ticket),
        ];
    }

    /**
     * Bloque "Cliente" del panel lateral. Lo básico (nombre/email/teléfono)
     * siempre disponible; empresa/idioma/ID externo/tickets·CSAT solo se
     * rellenan cuando el dato real existe — nunca se inventa un "Cliente ID"
     * o un CSAT que no tenemos (bug de honestidad de datos encontrado en QA:
     * el panel lateral llevaba estas columnas desde la Fase A sin
     * completarlas nunca).
     *
     * @return array<string, mixed>|null
     */
    private function mapCustomer(?Customer $customer): ?array
    {
        if (! $customer) {
            return null;
        }

        $company = $customer->company_id
            ? Company::find($customer->company_id)
            : null;

        $stats = $this->contactStats($customer);

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'company' => $company?->name,
            'customer_since_year' => $customer->created_at?->format('Y'),
            'language' => $customer->language,
            'external_id' => $customer->externalIdFor('prestashop') ?? $customer->externalIdFor('erp'),
            'is_banned' => $customer->banned_at !== null,
            'tickets_count' => $stats['tickets_count'] ?? null,
            'avg_csat' => $stats['avg_csat'] ?? null,
            'integrations' => $stats['integrations'] ?? [],
            'url_c360' => Route::has('contacts.show')
                ? route('contacts.show', $customer->id)
                : null,
        ];
    }

    /**
     * Reusa Contactos 360 (HelpdeskContacts) si está instalado y activo —
     * dependencia opcional en runtime, nunca declarada en module.json (mismo
     * sentido inverso que ya usa ese módulo para depender de HelpdeskTickets,
     * ver HelpdeskContacts\Services\ContactAggregatorService). Sin ese
     * módulo, estos datos simplemente no se muestran — no se calculan aquí
     * de forma aproximada.
     *
     * @return array{tickets_count?: int, avg_csat?: float, integrations?: array<int, array<string, mixed>>}
     */
    private function contactStats(Customer $customer): array
    {
        if (! Module::find('HelpdeskContacts')?->isEnabled()
            || ! class_exists(ContactAggregatorService::class)) {
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
            'integrations' => collect($resumen['integrations'] ?? [])
                ->filter(fn (array $i) => $i['connected'])
                ->values()
                ->all(),
        ];
    }

    /**
     * SLA legible para el bloque "Detalles" — reusa Ticket::slaEffectiveDueDate()/
     * getSlaStatusAttribute() ya existentes, no recalcula nada por su cuenta.
     *
     * @return array{label: string, color: string}|null
     */
    private function mapSla(Ticket $ticket): ?array
    {
        $status = $ticket->sla_status;

        if ($status === 'none') {
            return null;
        }

        $due = $ticket->slaEffectiveDueDate();

        return match ($status) {
            'breached' => ['label' => 'Vencido', 'color' => 'danger'],
            'warning' => ['label' => $due ? $due->diffForHumans() : 'Por vencer', 'color' => 'warning'],
            default => ['label' => $due ? $due->diffForHumans() : 'En plazo', 'color' => 'ok'],
        };
    }

    /**
     * @return array{0: array<int, array{disk: string, path: string, name: string}>, 1: array<int, array{name: string, path: string, disk: string, size: int}>}
     */
    private function storeAttachments(ComposeTicketMailRequest $request, Ticket $ticket): array
    {
        if (! $request->hasFile('attachments')) {
            return [[], []];
        }

        $disk = config('helpdesk.attachments.disk', 'local');
        $files = [];
        $meta = [];

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('helpdesk/tickets/'.$ticket->id.'/emails', $disk);
            $name = $file->getClientOriginalName();

            $files[] = ['disk' => $disk, 'path' => $path, 'name' => $name];
            $meta[] = ['name' => $name, 'path' => $path, 'disk' => $disk, 'size' => $file->getSize()];
        }

        return [$files, $meta];
    }

    /**
     * @param  Collection<int, TicketMail>  $mails
     */
    private function bulkResend($mails): int
    {
        $count = 0;

        foreach ($mails as $mail) {
            $ticket = $mail->ticket;
            if (! $ticket) {
                continue;
            }

            // Igual que resend(): crear una fila NUEVA en vez de reutilizar
            // $mail. dispatcher->send() llama markAsSent() sobre lo que le
            // pasemos — si le pasábamos el original, un reenvío masivo de un
            // email "rebotado"/"fallido" lo mutaba a "enviado" en el sitio,
            // borrando el historial del fallo (bug real encontrado en QA
            // manual de esta pantalla).
            $newMail = $this->createResendCopy($mail, $ticket, $mail->to);
            $this->dispatcher->send($newMail, $ticket, [], [], $this->dispatcher->resendableAttachments($mail));
            $count++;
        }

        return $count;
    }

    private function createResendCopy(TicketMail $mail, Ticket $ticket, string $to): TicketMail
    {
        return TicketMail::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'category_id' => $mail->category_id,
            'tags' => $mail->tags,
            'direction' => 'outbound',
            'from' => config('mail.from.address'),
            'to' => $to,
            'subject' => $mail->subject,
            'body_html' => $mail->body_html,
            'body_text' => $mail->body_text,
            'attachments' => $mail->attachments,
            'in_reply_to' => $mail->message_id,
            'status' => 'pending',
        ]);
    }
}

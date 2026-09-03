<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskTickets\Http\Requests\Managers\BulkTicketMailRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\ComposeTicketMailRequest;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Modules\HelpdeskTickets\Services\TicketMailAiSummaryService;
use Modules\HelpdeskTickets\Services\TicketMailDispatcher;
use Modules\HelpdeskTickets\Services\TicketVariableInterpolator;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * La bandeja global de LECTURA/browsing de helpdesk_ticket_mails se retiró
 * (ver plan de unificación con HelpdeskEmailActivity) — sigue vivo lo que no era
 * esa pantalla: la API JSON que consumen tickets-app.js y el composer
 * reubicado en TicketsCrudController::showFull(), y la pantalla dedicada de
 * scheduled() (correos programados, "estado de trabajo pendiente" sin
 * equivalente en EmailLog, que solo audita envíos que YA ocurrieron).
 */
class TicketMailsController extends Controller
{
    public function __construct(
        private readonly TicketMailDispatcher $dispatcher,
    ) {}

    /**
     * La bandeja global propia (managers.emails.index) se retiró — el
     * listado/browsing de correos de tickets vive ahora en
     * /panel/helpdeskemailactivity?module=HelpdeskTickets (auditoría cross-módulo
     * unificada, ver plan de unificación). Este método SIGUE VIVO porque
     * tickets-app.js lo consume como API JSON en dos sitios que no tienen
     * nada que ver con "la página": el modal de "Entregabilidad de correo" y
     * el refetch de stats tras enviar/reenviar — ambos piden con
     * $.getJSON(), que manda Accept: application/json y entra por la rama
     * JSON de abajo. Solo la navegación de NAVEGADOR (sin ese header) se
     * redirige a la nueva ubicación.
     */
    public function index(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('viewAny', TicketMail::class);

        abort_if(! helpdesk_tickets_enabled(), 404);

        if (! $request->wantsJson()) {
            return redirect()->route('helpdeskemailactivity.index', array_filter([
                'module' => 'HelpdeskTickets',
                'search' => $request->query('search'),
            ]));
        }

        $mails = $this->filteredQuery($request)->paginate(50)->appends($request->query());

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

    /**
     * Cola de correos programados (scheduled_at futuro, aún no enviados) —
     * cross-ticket a propósito: un agente revisando "qué sale programado
     * esta semana" necesita verlo de todos los tickets a la vez, no ticket
     * por ticket. No tiene equivalente en EmailLog (que solo audita envíos
     * que YA ocurrieron) — ver Fase D del plan de unificación. La tabla la
     * hidrata el propio JS con la MISMA API JSON de index() (?view=scheduled),
     * sin duplicar la consulta aquí.
     */
    public function scheduled(): View
    {
        $this->authorize('viewAny', TicketMail::class);

        abort_if(! helpdesk_tickets_enabled(), 404);

        return view('helpdesktickets::managers.emails.scheduled', [
            'dataUrl' => route('manager.helpdesk.tickets.emails.index', ['view' => 'scheduled']),
            'bulkUrl' => route('manager.helpdesk.tickets.emails.bulk'),
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
     * un email). El texto y el asunto (si la macro lo trae, ver
     * Macro::actionSpecs()['reply']['optional']) se interpolan con las
     * variables del ticket dado (mismo TicketVariableInterpolator que ya usa
     * MacroExecutor). Sin 'subject' en la macro, se devuelve null — el
     * composer (ticket-detail.js) cae entonces al nombre de la macro como
     * aproximación, igual que hacía antes de existir este campo.
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
                // A diferencia de body (siempre string, cadena vacía si
                // falta), subject es realmente opcional: null cuando la
                // macro no lo trae, para que el composer sepa que debe caer
                // al nombre de la macro en su lugar (ver ticket-detail.js).
                $subject = filled($reply['subject'] ?? null) ? (string) $reply['subject'] : null;

                return [
                    'id' => $macro->id,
                    'name' => $macro->name,
                    'description' => $macro->description,
                    'body' => $ticket ? $interpolator->interpolate($body, $ticket) : $body,
                    'subject' => $subject !== null
                        ? ($ticket ? $interpolator->interpolate($subject, $ticket) : $subject)
                        : null,
                ];
            })
            ->filter()
            ->values();

        return response()->json(['success' => true, 'templates' => $templates]);
    }

    /**
     * Direcciones desde las que se puede enviar. La primera es siempre la
     * global (config('mail.from.address')), que es la que realmente autentica
     * el SMTP; se le suman los buzones IMAP configurados cuyo usuario es un
     * email, que en la práctica son los alias de soporte del mismo dominio.
     *
     * @return array<int, string>
     */
    public static function availableSenders(): array
    {
        $senders = [];

        $global = config('mail.from.address');
        if (is_string($global) && filter_var($global, FILTER_VALIDATE_EMAIL)) {
            $senders[] = $global;
        }

        foreach (app(TicketEmailChannelsRepository::class)->all() as $channel) {
            $username = $channel['username'] ?? null;
            if (is_string($username) && filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $senders[] = $username;
            }
        }

        return array_values(array_unique($senders));
    }

    /**
     * Devuelve $requested solo si está en la lista de remitentes permitidos;
     * si no, el remitente global. Nunca el valor crudo del formulario.
     */
    private static function resolveSender(?string $requested): ?string
    {
        $allowed = self::availableSenders();

        return $requested !== null && in_array($requested, $allowed, true)
            ? $requested
            : ($allowed[0] ?? config('mail.from.address'));
    }

    public function store(ComposeTicketMailRequest $request): JsonResponse
    {
        $ticket = Ticket::findOrFail($request->integer('ticket_id'));

        $this->authorize('view', $ticket);
        $this->authorize('create', TicketMail::class);

        $validated = $request->validated();

        [$attachmentFiles, $attachmentMeta] = $this->storeAttachments($request, $ticket);

        $scheduledAt = $validated['scheduled_at'] ?? null;

        // El 'to' validado solo comprueba formato de email — sin esto,
        // cualquiera con permiso de redactar podía usar la dirección
        // corporativa para escribirle a un destinatario arbitrario. Por
        // defecto se fija al cliente del ticket; salirse de eso exige
        // permiso explícito + queda auditado (ver resolveOutboundRecipient).
        $to = $this->resolveOutboundRecipient($validated['to'] ?? null, $ticket);

        // cc/bcc también estaban abiertos a cualquier email válido — se
        // restringen a direcciones que ya participan del ticket (cliente,
        // correspondencia previa del hilo, agentes que lo siguen).
        $participants = $this->participantEmails($ticket);
        $this->assertParticipants($validated['cc'] ?? [], $participants);
        $this->assertParticipants($validated['bcc'] ?? [], $participants);

        $mail = TicketMail::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'category_id' => $validated['category_id'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'direction' => 'outbound',
            'is_internal' => $validated['is_internal'] ?? false,
            // Remitente elegido por el agente, siempre validado contra la
            // lista cerrada de direcciones configuradas. Cae al remitente
            // global si no se manda ninguno o si el que llega no está en la
            // lista (nunca se confía en el valor del formulario a secas).
            'from' => self::resolveSender($validated['from'] ?? null),
            'to' => $to,
            'cc' => isset($validated['cc']) ? implode(',', $validated['cc']) : null,
            'bcc' => isset($validated['bcc']) ? implode(',', $validated['bcc']) : null,
            'subject' => $validated['subject'],
            'body_html' => $validated['body'],
            'body_text' => strip_tags($validated['body']),
            'attachments' => $attachmentMeta ?: null,
            'status' => $scheduledAt ? 'scheduled' : 'pending',
            'scheduled_at' => $scheduledAt,
            // Solo tiene sentido en un envío programado: en un envío
            // inmediato no hay ventana en la que el cliente pueda adelantarse.
            'cancel_if_customer_replies' => $scheduledAt
                ? (bool) ($validated['cancel_if_customer_replies'] ?? false)
                : false,
        ]);

        self::forgetStatsCache();

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

        // Defensa en profundidad: un correo ENTRANTE no tiene una dirección
        // de reenvío segura por defecto — su 'to' es la propia bandeja de
        // soporte (quien lo recibió), no el cliente. "Reenviarlo" sin más
        // mandaría un correo real a soporte con el asunto/cuerpo del
        // entrante, el efecto opuesto al que sugiere el botón (bug real
        // encontrado en QA: TicketDetailDataController::data() ya evita
        // exponer este endpoint para un mail entrante vía last_outbound_mail,
        // pero este endpoint es genérico y puede alcanzarse con el id de
        // cualquier TicketMail). Para uno de SALIDA se mantiene el
        // comportamiento existente (usa mail->to si no se manda otro).
        if ($mail->direction === 'inbound' && ! $request->filled('to')) {
            return response()->json([
                'success' => false,
                'message' => 'Este correo es de entrada: indica un destinatario para reenviarlo.',
            ], 422);
        }

        $requestedTo = $request->filled('to') ? $request->string('to')->toString() : $mail->to;

        // Mismo guard que en store(): reenviar a algo distinto del cliente
        // del ticket exige permiso explícito + queda auditado.
        $to = $this->resolveOutboundRecipient($requestedTo, $ticket);

        $newMail = $this->createResendCopy($mail, $ticket, $to, $request->boolean('without_attachments'));

        $this->dispatcher->send($newMail, $ticket, [], [], $this->dispatcher->resendableAttachments($mail));

        self::forgetStatsCache();

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

        self::forgetStatsCache();

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

        // reorder() quita el latest() de filteredQuery() (pensado para el
        // listado paginado): lazyById() pagina forzando su propio ORDER BY
        // id y pisaría ese orden en cada iteración si se dejara.
        $query = $this->filteredQuery($request)->reorder();

        if ($request->filled('ids')) {
            $query->whereIn('id', (array) $request->input('ids'));
        }

        // cursor() no aplicaba el with() de filteredQuery() (hidrataba fila a
        // fila sin eager load real): cada email disparaba sus propias
        // queries de ticket/ticket.customer/user/category. lazyById() sí
        // respeta el eager loading, paginando por id en bloques de 500. Al
        // paginar por id no es compatible con el limit(5000) previo — se
        // sustituye el tope fijo por la exportación completa del filtro.
        $rows = $query->lazyById(500);
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

        self::forgetStatsCache();

        return response()->json(['success' => true, 'message' => 'Email eliminado.']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Resuelve el destinatario real de un envío (store()/resend()). Por
     * defecto (campo vacío, o igual al cliente del ticket) siempre es el
     * cliente — NUNCA un valor arbitrario sin más. Pedir explícitamente otra
     * dirección exige el permiso 'helpdesk.tickets.emails.send_to_any' y
     * queda registrado en el historial del ticket (SEC-07 item 5): la
     * dirección corporativa no debe poder usarse para escribirle a cualquier
     * tercero sin dejar rastro.
     */
    private function resolveOutboundRecipient(?string $requested, Ticket $ticket): string
    {
        $customerEmail = $ticket->customer?->email;
        $requested = $requested !== null && $requested !== '' ? mb_strtolower(trim($requested)) : null;

        if ($requested === null) {
            abort_if(! $customerEmail, 422, 'El ticket no tiene un cliente con email al que enviar el correo.');

            return $customerEmail;
        }

        if ($customerEmail && $requested === mb_strtolower($customerEmail)) {
            return $customerEmail;
        }

        $this->authorize('sendToAnyRecipient', TicketMail::class);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'action_type' => 'mail_sent_to_arbitrary_recipient',
            'old_value' => $customerEmail,
            'new_value' => $requested,
            'metadata' => ['description' => 'Correo del ticket enviado a un destinatario distinto del cliente'],
        ]);

        return $requested;
    }

    /**
     * Direcciones "de confianza" para ir en Cc/Bcc de un correo de este
     * ticket: el propio cliente, cualquier dirección que ya haya aparecido
     * en el hilo (to/cc/bcc de correos previos del ticket) y los agentes que
     * lo siguen — no cualquier email válido.
     *
     * @return list<string>
     */
    private function participantEmails(Ticket $ticket): array
    {
        $emails = collect();

        if ($ticket->customer?->email) {
            $emails->push(mb_strtolower($ticket->customer->email));
        }

        TicketMail::query()
            ->where('ticket_id', $ticket->id)
            ->get(['to', 'cc', 'bcc'])
            ->each(function (TicketMail $mail) use ($emails) {
                foreach (array_filter([$mail->to, $mail->cc, $mail->bcc]) as $field) {
                    foreach (explode(',', $field) as $address) {
                        $address = mb_strtolower(trim($address));

                        if ($address !== '') {
                            $emails->push($address);
                        }
                    }
                }
            });

        $ticket->watchers()->with('user:id,email')->get()->each(function ($watcher) use ($emails) {
            if ($watcher->user?->email) {
                $emails->push(mb_strtolower($watcher->user->email));
            }
        });

        return $emails->unique()->values()->all();
    }

    /**
     * @param  list<string>  $addresses
     * @param  list<string>  $participants
     */
    private function assertParticipants(array $addresses, array $participants): void
    {
        $invalid = collect($addresses)
            ->reject(fn (string $address) => in_array(mb_strtolower(trim($address)), $participants, true))
            ->values();

        if ($invalid->isNotEmpty()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Estos destinatarios en copia no participan en el ticket: '.$invalid->implode(', '),
            ], 422));
        }
    }

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
     * @return array{total: int, bounced: int, bounce_rate: float, scheduled: int, internal: int, opened_rate: float, clicked_rate: float, avg_latency: ?float, queue_waiting: int}
     */
    /**
     * KPIs de cabecera (index()/refetch tras enviar) — no son datos
     * transaccionales, así que se cachean unos segundos. store()/resend()/
     * bulk()/destroy() invalidan esta clave explícitamente: el refetch por
     * AJAX que hace tickets-app.js justo después de esas acciones ya tuvo un
     * bug real (QA manual) por quedarse con los valores del primer GET, y un
     * TTL corto sin invalidar reintroduciría el mismo síntoma durante la
     * ventana de caché.
     */
    private const STATS_CACHE_KEY = 'helpdeskticketmails:stats';

    private const STATS_CACHE_TTL_SECONDS = 45;

    /**
     * Ventana considerada para opened_rate/avg_latency (ver
     * openTrackingStats()) — evita escanear el histórico completo de
     * envíos solo para un KPI de cabecera.
     */
    private const OPEN_STATS_WINDOW_DAYS = 90;

    private function stats(): array
    {
        return Cache::remember(self::STATS_CACHE_KEY, self::STATS_CACHE_TTL_SECONDS, function () {
            $total = TicketMail::outbound()->where('is_internal', false)->count();
            $bounced = TicketMail::outbound()->where('is_internal', false)->bounced()->count();
            $scheduled = TicketMail::scheduled()->count();
            $internal = TicketMail::outbound()->internal()->count();

            $openTracking = $this->openTrackingStats();

            return [
                'total' => $total,
                'bounced' => $bounced,
                'bounce_rate' => $total > 0 ? round($bounced / $total * 100, 1) : 0.0,
                'scheduled' => $scheduled,
                'internal' => $internal,
                'opened_rate' => $openTracking['opened_rate'],
                'clicked_rate' => $openTracking['clicked_rate'],
                'avg_latency' => $openTracking['avg_latency'],
                'queue_waiting' => $this->queueWaiting(),
            ];
        });
    }

    private static function forgetStatsCache(): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }

    /**
     * Tasa de apertura/clic y latencia media de envío de los últimos
     * self::OPEN_STATS_WINDOW_DAYS días, con una sola agregación en SQL
     * (JOIN + SUM/AVG) en vez de traer un EmailLog por cada TicketMail y
     * promediar en PHP — antes era un pluck('message_id') SIN ventana
     * temporal seguido de EmailLog::withCount('opens')->whereIn(...)
     * hidratando un modelo por id.
     *
     * Aperturas/clics y latencia salen de EmailLog (módulo HelpdeskEmailActivity),
     * cruzado por message_id — TicketMail no trackea nada de eso, sería
     * duplicar una fuente de verdad que ya existe. TicketMail.message_id
     * se guarda con los ángulos <...> (formato de cabecera RFC 5322);
     * EmailLog.message_id se guarda SIN ellos (ver LogEmailQueued::
     * ensureMessageId()) — sin el TRIM() de abajo el JOIN nunca encuentra
     * nada (bug real encontrado al probar el pixel en vivo). MySQL
     * TRIM(char FROM str) solo quita UN carácter por llamada (a diferencia
     * de PHP trim($id, '<>')), así que hacen falta dos TRIM anidados, uno
     * por cada símbolo del delimitador.
     *
     * Los clics se cuentan por EMAIL (COUNT DISTINCT email_log_link_id vía
     * el JOIN con email_log_links), no por hit — un mismo destinatario
     * clicando el mismo enlace 3 veces cuenta como "1 correo con clic", no
     * infla el numerador de clicked_rate.
     *
     * La conexión 'helpdesk' (TicketMail) y la conexión por defecto
     * (EmailLog) apuntan a la misma base de datos física en este entorno,
     * así que el JOIN entre ambas tablas es válido en una sola query.
     *
     * @return array{opened_rate: float, clicked_rate: float, avg_latency: ?float}
     */
    private function openTrackingStats(): array
    {
        $normalizedTicketMails = TicketMail::outbound()
            ->where('is_internal', false)
            ->whereNotNull('message_id')
            ->where('created_at', '>=', now()->subDays(self::OPEN_STATS_WINDOW_DAYS))
            ->selectRaw("id, TRIM(BOTH '>' FROM TRIM(BOTH '<' FROM message_id)) AS normalized_message_id");

        $openCounts = DB::table('email_log_opens')
            ->selectRaw('email_log_id, COUNT(*) AS opens_count')
            ->groupBy('email_log_id');

        $clickCounts = DB::table('email_log_clicks')
            ->join('email_log_links', 'email_log_links.id', '=', 'email_log_clicks.email_log_link_id')
            ->selectRaw('email_log_links.email_log_id, COUNT(*) AS clicks_count')
            ->groupBy('email_log_links.email_log_id');

        $row = DB::connection('helpdesk')
            ->query()
            ->fromSub($normalizedTicketMails, 'tm')
            ->join('email_logs', 'email_logs.message_id', '=', 'tm.normalized_message_id')
            ->leftJoinSub($openCounts, 'oc', 'oc.email_log_id', '=', 'email_logs.id')
            ->leftJoinSub($clickCounts, 'cc', 'cc.email_log_id', '=', 'email_logs.id')
            ->selectRaw(implode(', ', [
                'COUNT(*) AS matched',
                'SUM(CASE WHEN oc.opens_count > 0 THEN 1 ELSE 0 END) AS opened',
                'SUM(CASE WHEN cc.clicks_count > 0 THEN 1 ELSE 0 END) AS clicked',
                'AVG(ABS(TIMESTAMPDIFF(SECOND, email_logs.created_at, email_logs.sent_at))) AS avg_latency_seconds',
            ]))
            ->first();

        $matched = (int) ($row->matched ?? 0);

        return [
            'opened_rate' => $matched > 0 ? round(((int) $row->opened) / $matched * 100, 1) : 0.0,
            'clicked_rate' => $matched > 0 ? round(((int) $row->clicked) / $matched * 100, 1) : 0.0,
            'avg_latency' => $row->avg_latency_seconds !== null ? round((float) $row->avg_latency_seconds, 1) : null,
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

    /**
     * Modal 09 "Cancelar envío programado".
     *
     * Dos salidas, como en el mockup: dejarlo como borrador (se conserva el
     * contenido y deja de tener hora, así que el comando de envío ya no lo
     * recoge) o eliminarlo del todo. No se toca ninguna cola: el envío
     * programado no crea un job, lo recoge SendScheduledTicketMailsCommand
     * consultando por scheduled_at, así que basta con cambiar la fila.
     */
    public function cancelScheduled(Request $request, TicketMail $mail): JsonResponse
    {
        $ticket = $mail->ticket;
        abort_if(! $ticket, 404);
        $this->authorize('view', $ticket);
        $this->authorize('create', TicketMail::class);

        abort_unless($mail->status === 'scheduled', 422, 'Este correo ya no está programado.');

        $mode = $request->string('mode')->toString();

        if ($mode === 'delete') {
            $mail->delete();

            return response()->json(['success' => true, 'message' => 'Envío programado eliminado.']);
        }

        $mail->update(['status' => 'draft', 'scheduled_at' => null, 'cancel_if_customer_replies' => false]);

        return response()->json(['success' => true, 'message' => 'Envío cancelado: el correo queda como borrador.']);
    }

    /**
     * Modal 10 "Vincular email a ticket".
     *
     * Mueve un correo suelto (o mal clasificado) al ticket indicado. Si se
     * pide mover el hilo entero, arrastra también los correos que comparten
     * su cadena de References/In-Reply-To, que es lo que de verdad hace que
     * un hilo quede partido entre dos tickets.
     */
    public function linkToTicket(Request $request, TicketMail $mail): JsonResponse
    {
        $validated = $request->validate([
            'ticket_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_tickets,id'],
            'move_thread' => ['nullable', 'boolean'],
        ]);

        $target = Ticket::findOrFail($validated['ticket_id']);
        $this->authorize('update', $target);

        $moved = 1;

        DB::connection('helpdesk')->transaction(function () use ($request, $mail, $target, &$moved) {
            $mail->update(['ticket_id' => $target->id]);

            if ($request->boolean('move_thread') && $mail->message_id) {
                // Correos del MISMO hilo: los que responden a éste y aquellos a
                // los que éste responde. Se limita al ticket de origen para no
                // arrastrar correos de terceros que casualmente compartan id.
                $moved += TicketMail::query()
                    ->where('ticket_id', '!=', $target->id)
                    ->where(function ($q) use ($mail) {
                        $q->where('in_reply_to', $mail->message_id);
                        if ($mail->in_reply_to) {
                            $q->orWhere('message_id', $mail->in_reply_to)
                                ->orWhere('in_reply_to', $mail->in_reply_to);
                        }
                    })
                    ->update(['ticket_id' => $target->id]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => $moved > 1
                ? "Se movieron {$moved} correos a {$target->ticket_number}."
                : "Correo vinculado a {$target->ticket_number}.",
            'ticket_id' => $target->id,
        ]);
    }

    /**
     * Modal 06 "Email rebotado": corrige el destinatario y vuelve a enviar.
     *
     * Las tres acciones del mockup en una sola transacción, porque tiene poco
     * sentido dejarlas a medias: actualizar el email del contacto, apuntar la
     * dirección vieja en la lista de supresión para que no se reintente
     * sola, y reenviar la copia al destinatario corregido. Deja además una
     * nota interna en el ticket con lo ocurrido, que es la traza que el
     * agente asignado necesita para entender por qué cambió el contacto.
     */
    public function fixBounce(Request $request, TicketMail $mail): JsonResponse
    {
        $this->authorize('resend', $mail);

        $ticket = $mail->ticket;
        abort_if(! $ticket, 404);

        $validated = $request->validate([
            'to' => ['required', 'email', 'max:255'],
            'update_contact' => ['nullable', 'boolean'],
            'suppress_old' => ['nullable', 'boolean'],
        ]);

        $oldAddress = $mail->to;
        $newAddress = $validated['to'];

        return DB::connection('helpdesk')->transaction(function () use ($request, $mail, $ticket, $oldAddress, $newAddress) {
            if ($request->boolean('update_contact') && $ticket->customer) {
                $ticket->customer->update(['email' => $newAddress]);
            }

            if ($request->boolean('suppress_old') && $oldAddress) {
                TicketEmailBlacklist::updateOrCreate(
                    ['type' => 'email', 'value' => $oldAddress],
                    [
                        'reason' => 'Rebote permanente en '.$ticket->ticket_number.': '.($mail->delivery_error ?: 'sin detalle'),
                        'is_active' => true,
                        'added_by' => auth()->id(),
                    ],
                );
            }

            $newMail = $this->createResendCopy($mail, $ticket, $newAddress);

            TicketNote::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'body' => sprintf(
                    'Rebote corregido: el envío a %s falló (%s) y se ha reenviado a %s.',
                    $oldAddress ?: '—',
                    $mail->delivery_error ?: 'sin detalle',
                    $newAddress,
                ),
            ]);

            app(TicketMailDispatcher::class)->send(
                $newMail,
                $ticket,
                $newMail->cc ? array_map('trim', explode(',', $newMail->cc)) : [],
                $newMail->bcc ? array_map('trim', explode(',', $newMail->bcc)) : [],
                app(TicketMailDispatcher::class)->resendableAttachments($newMail),
            );

            return response()->json([
                'success' => true,
                'message' => 'Destinatario corregido y correo reenviado a '.$newAddress,
                'mail_id' => $newMail->id,
            ]);
        });
    }

    private function createResendCopy(TicketMail $mail, Ticket $ticket, string $to, bool $withoutAttachments = false): TicketMail
    {
        return TicketMail::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'category_id' => $mail->category_id,
            'tags' => $mail->tags,
            'direction' => 'outbound',
            'from' => $mail->from ?: config('mail.from.address'),
            'to' => $to,
            'subject' => $mail->subject,
            'body_html' => $mail->body_html,
            'body_text' => $mail->body_text,
            // "Reenviar sin adjuntos" del modal 07: útil cuando el rebote fue
            // por tamaño del mensaje, o cuando solo hace falta que el texto
            // llegue otra vez.
            'attachments' => $withoutAttachments ? null : $mail->attachments,
            'in_reply_to' => $mail->message_id,
            'status' => 'pending',
        ]);
    }
}

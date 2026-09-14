<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskTickets\Http\Requests\Managers\BulkTicketMailRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\ComposeTicketMailRequest;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Policies\TicketPolicy;
use Modules\HelpdeskTickets\Services\TicketAttachmentSecurityService;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Modules\HelpdeskTickets\Services\TicketMailAiSummaryService;
use Modules\HelpdeskTickets\Services\TicketMailDispatcher;
use Modules\HelpdeskTickets\Services\TicketMailInboxService;
use Modules\HelpdeskTickets\Services\TicketMailRecipientResolver;
use Modules\HelpdeskTickets\Services\TicketVariableInterpolator;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * La bandeja global de LECTURA/browsing de helpdesk_ticket_mails se retiró
 * (ver plan de unificación con HelpdeskEmailActivity) — sigue vivo lo que no era
 * esa pantalla: la API JSON que consumen tickets-app.js y el composer, que
 * vivió un tiempo en TicketsCrudController::showFull() (ficha completa,
 * retirada el 8-sep-2026) y hoy solo se sirve desde el listado, y la
 * pantalla dedicada de scheduled() (correos programados, "estado de
 * trabajo pendiente" sin equivalente en EmailLog, que solo audita envíos
 * que YA ocurrieron).
 */
class TicketMailsController extends Controller
{
    public function __construct(
        private readonly TicketMailDispatcher $dispatcher,
        private readonly TicketMailInboxService $inbox,
        private readonly TicketMailRecipientResolver $recipients,
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

        $mails = $this->inbox->filteredQuery($request)->paginate(50)->appends($request->query());

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
            'stats' => $this->inbox->stats(),
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

        // 'viewAny' de arriba autoriza la pantalla de correos en general,
        // no ESTE ticket concreto: sin este check un agente con solo
        // helpdesk.tickets.emails.view podía pedir ?ticket_id=<ajeno> y
        // leer en el JSON el NIF/saldo/límite de crédito ERP y el contacto
        // de un cliente al que no tiene acceso (14-sep-2026, auditoría de
        // seguridad) — mismo patrón ya corregido en CannedRepliesController.
        // accessibleTo(), no authorize('view', ...): este endpoint entra con
        // helpdesk.tickets.emails.view, no helpdesk.tickets.view — exigir
        // este último aquí rompería al agente legítimo que solo tiene el
        // primero (reproducido con
        // TicketMailsControllerTest::test_templates_includes_interpolated_subject...).
        if ($ticket) {
            abort_unless(app(TicketPolicy::class)->accessibleTo(auth()->user(), $ticket), 403);
        }

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
        // permiso explícito + queda auditado (ver TicketMailRecipientResolver).
        $to = $this->recipients->resolveOutbound($validated['to'] ?? null, $ticket);

        // cc/bcc también estaban abiertos a cualquier email válido — se
        // restringen a direcciones que ya participan del ticket (cliente,
        // correspondencia previa del hilo, agentes que lo siguen).
        $participants = $this->recipients->participants($ticket);
        $this->recipients->assertParticipants($validated['cc'] ?? [], $participants);
        $this->recipients->assertParticipants($validated['bcc'] ?? [], $participants);

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

        TicketMailInboxService::forgetStatsCache();

        if ($scheduledAt) {
            return response()->json([
                'success' => true,
                'message' => 'Email programado correctamente.',
                'data' => $mail->fresh()->toListRow(),
            ], 201);
        }

        $sent = $this->dispatcher->send($mail, $ticket, $validated['cc'] ?? [], $validated['bcc'] ?? [], $attachmentFiles);

        if (! $sent) {
            return response()->json([
                'success' => false,
                'message' => $mail->fresh()->delivery_error ?? 'No se pudo enviar el email.',
                'data' => $mail->fresh()->toListRow(),
            ], 422);
        }

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
        $to = $this->recipients->resolveOutbound($requestedTo, $ticket);

        $newMail = $this->createResendCopy($mail, $ticket, $to, $request->boolean('without_attachments'));

        $sent = $this->dispatcher->send($newMail, $ticket, [], [], $this->dispatcher->resendableAttachments($mail));

        TicketMailInboxService::forgetStatsCache();

        if (! $sent) {
            return response()->json([
                'success' => false,
                'message' => $newMail->fresh()->delivery_error ?? 'No se pudo reenviar el email.',
                'data' => $newMail->fresh()->toListRow(),
            ], 422);
        }

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

        TicketMailInboxService::forgetStatsCache();

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
        $query = $this->inbox->filteredQuery($request)->reorder();

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

        TicketMailInboxService::forgetStatsCache();

        return response()->json(['success' => true, 'message' => 'Email eliminado.']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

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
        $attachmentSecurity = app(TicketAttachmentSecurityService::class);

        foreach ($request->file('attachments') as $file) {
            $attachmentSecurity->assertSafe($file);
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
            if ($this->dispatcher->send($newMail, $ticket, [], [], $this->dispatcher->resendableAttachments($mail))) {
                $count++;
            }
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

        // Antes solo autorizaba el DESTINO: un agente que solo fuera
        // assignee del ticket destino (pasa TicketPolicy::update() por ese
        // atajo, sin ningún permiso helpdesk.tickets.emails.*) podía mover
        // aquí cualquier TicketMail existente — incluido uno de un ticket al
        // que no tiene acceso — arrastrando además todo el hilo con
        // move_thread (14-sep-2026, auditoría de seguridad).
        if ($mail->ticket) {
            $this->authorize('update', $mail->ticket);
        }

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

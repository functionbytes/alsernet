<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Services\CustomerSummaryService;
use Modules\HelpdeskTickets\Services\EmailLogLookupService;
use Modules\HelpdeskTickets\Services\HelpdeskTicketBridgeService;
use Modules\HelpdeskTickets\Services\MentionService;
use Nwidart\Modules\Facades\Module;
use Throwable;

/**
 * Extraído de TicketsCrudController (30-ago-2026, controller de 1017 líneas)
 * — el endpoint data() y sus 6 helpers privados construyen el JSON del panel
 * de detalle del ticket (Hilo, actividad, correo, notas, relacionados, etc.),
 * un bloque autocontenido de ~340 líneas sin relación con el CRUD. Sin
 * cambios de comportamiento, solo movimiento. mapCustomerDetail()/
 * contactStats() migrados a CustomerSummaryService (antes duplicados casi
 * al carácter con TicketMailsController, consolidados el mismo día).
 */
class TicketDetailDataController extends Controller
{
    public function __construct(
        private readonly CustomerSummaryService $customerSummary,
        private readonly EmailLogLookupService $emailLogLookup,
        private readonly MentionService $mentionService,
    ) {}

    /**
     * Adjuntos de un item del hilo, con nombre y tamaño legibles.
     *
     * attachment_urls guarda rutas dentro del disco público. El tamaño se
     * consulta al disco y no se cachea: son pocos ficheros por mensaje y un
     * tamaño desactualizado sería peor que uno ausente. Si el fichero ya no
     * está (purgado, movido), se devuelve sin tamaño en vez de romper la
     * fila entera.
     *
     * @return list<array{name: string, size: ?string, url: string}>
     */
    private function threadAttachments(TicketItem $item): array
    {
        $paths = $item->attachment_urls ?? [];

        if (! is_array($paths) || $paths === []) {
            return [];
        }

        $disk = Storage::disk('public');

        return collect($paths)->map(function ($path) use ($disk): array {
            $path = (string) $path;
            $size = null;

            try {
                if ($disk->exists($path)) {
                    $size = $this->humanSize($disk->size($path));
                }
            } catch (Throwable) {
                // Disco no disponible o ruta inválida: se informa del fichero
                // igualmente, solo que sin peso.
            }

            return [
                'name' => basename($path),
                'size' => $size,
                'url' => $disk->url($path),
            ];
        })->values()->all();
    }

    /**
     * Traza de entrega de un mensaje saliente, para la línea
     * "entregado 10:42:11" del mockup.
     *
     * Devuelve null cuando no hay correo o cuando aún no consta entregado: el
     * mockup enseña siempre la traza porque su ejemplo está entregado, pero
     * afirmar una entrega que el proveedor no ha confirmado sería inventarla.
     *
     * @return array{label: string, at: string}|null
     */
    private function threadDelivery(?TicketMail $mail): ?array
    {
        if (! $mail) {
            return null;
        }

        if ($mail->delivered_at) {
            return ['label' => 'entregado', 'at' => $mail->delivered_at->format('H:i:s')];
        }

        // Aceptado por el servidor pero sin confirmación de entrega: se dice
        // exactamente eso, que no es lo mismo.
        if ($mail->sent_at && $mail->status === 'sent') {
            return ['label' => 'enviado', 'at' => $mail->sent_at->format('H:i:s')];
        }

        return null;
    }

    /** Tamaño en la unidad más legible (el mockup los enseña como "142 KB"). */
    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $kb = $bytes / 1024;

        return $kb < 1024
            ? round($kb).' KB'
            : round($kb / 1024, 1).' MB';
    }

    public function data(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load(['items' => fn ($q) => $q->orderBy('created_at'), 'items.user', 'items.author', 'followups', 'aiSuggestedCategory', 'watchers.user']);

        // Correo asociado a cada item, en UNA consulta: los botones "Reenviar"
        // y "Ver original" del hilo operan sobre TicketMail, no sobre el item,
        // y sin este mapa habría que consultarlo fila a fila.
        $mailsByItem = $ticket->mails()
            ->whereNotNull('ticket_item_id')
            ->get(['id', 'ticket_item_id', 'status', 'delivered_at', 'sent_at'])
            ->keyBy('ticket_item_id');

        $thread = $ticket->items->map(fn ($item) => [
            'id' => $item->id,
            'type' => $item->type,
            'is_internal' => (bool) $item->is_internal,
            'sender_name' => $item->sender_name,
            'from_agent' => $item->isFromAgent(),
            'body' => $item->content,
            // TranslateIncomingTicketMessage ya calcula translated_body/
            // source_locale para cada mensaje del cliente en un idioma
            // distinto al del agente, pero este endpoint (el que realmente
            // alimenta el panel de /panel/helpdesk/tickets, a diferencia de
            // la "ficha completa" show-full) nunca los exponía -- el agente
            // no se enteraba de que había una traducción disponible
            // (detectado 3-sep-2026 probando el flujo real con un mensaje
            // en inglés).
            'translated_body' => $item->translated_body,
            'source_language_name' => $item->source_language_name,
            'attachment_count' => $item->attachment_count,
            'created_at' => $item->created_at?->toIso8601String(),
            'created_at_human' => $item->created_at?->diffForHumans(),
            // --- Campos del hilo del mockup ---
            // Hora corta: el hilo agrupa por día con su separador, así que
            // dentro de cada grupo la fecha sobra y "hace 3 semanas" no deja
            // ordenar mentalmente dos mensajes del mismo día.
            'time' => $item->created_at?->format('H:i'),
            // Rol de quien escribe: es lo que distingue de un vistazo la
            // columna del cliente de la del agente.
            'role' => $item->isFromAgent()
                ? ($item->user_id ? __('helpdesktickets::helpdesktickets.thread.role_agent') : __('helpdesktickets::helpdesktickets.thread.role_system'))
                : __('helpdesktickets::helpdesktickets.thread.role_customer'),
            // Canal por el que entró/salió el mensaje. Se hereda del ticket:
            // el item no guarda origen propio, y todos los de un ticket
            // comparten el suyo salvo los eventos del sistema.
            'channel' => $item->type === 'message' ? ($ticket->source ?: null) : null,
            'direction' => $item->type === 'message'
                ? ($item->isFromAgent() ? 'outbound' : 'inbound')
                : null,
            // Ficheros con nombre y peso, no solo el recuento: el mockup los
            // lista como chips con su tamaño para poder decidir si abrirlos.
            'attachments' => $this->threadAttachments($item),
            // Correo real detrás de este mensaje, si lo hubo. Es lo que permite
            // reenviarlo o ver su fuente: un mensaje del hilo que nunca salió
            // por correo (nota interna, evento, mensaje de widget) no tiene
            // nada que reenviar, y sus botones no deben ofrecerse.
            'mail_id' => $mailsByItem->get($item->id)?->id,
            'delivery' => $this->threadDelivery($mailsByItem->get($item->id)),
        ])->values();

        // OJO: este proyecto tiene una copia vendored antigua de
        // spatie/laravel-activitylog dentro de modules/Activity/vendor/...
        // que el autoload PSR-4 resuelve ANTES que vendor/spatie/... (mismo
        // classmap-split ya documentado en el proyecto). Esa copia antigua
        // define activities() directamente (no activitiesAsSubject(), que
        // es de la copia nueva en vendor/ raíz y aquí nunca se carga) —
        // confirmado en runtime: activitiesAsSubject() lanzaba
        // BadMethodCallException real.
        // Total real ANTES de recortar con limit(20) — el JS lo usa para
        // avisar "mostrando los 20 más recientes" en vez de dejar el corte
        // silencioso (hallazgo LOW del audit: con 32+ entradas en un ticket
        // longevo, el badge del riel de iconos nunca podía delatar que
        // había más historial).
        $activityTotalCount = $ticket->activities()->count();

        $activity = $ticket->activities()
            ->with('causer')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'description' => $a->description,
                // ->name a secas devolvía SIEMPRE null: el User de esta app
                // guarda firstname/lastname y no tiene columna 'name', así
                // que la pestaña Actividad se pintaba entera sin autor.
                'causer' => $this->causerName($a->causer),
                'causer_kind' => $this->causerKind($a),
                'created_at' => $a->created_at?->toIso8601String(),
                'created_at_human' => $a->created_at?->diffForHumans(),
            ])->values();

        $attachmentsDisk = config('helpdesk.attachments.disk', 'local');

        // Adjuntos escritos por el panel de agente: rutas de storage dentro de
        // TicketItem.attachment_urls.
        $itemFiles = $ticket->items
            ->filter(fn ($item) => $item->hasAttachments())
            ->flatMap(fn ($item) => collect($item->attachment_urls)->values()->map(fn ($path, $index) => [
                // Storage::putFile() genera un hash como nombre real de
                // fichero (p.ej. 'NlZV0tAIXTf6d73vPYZyKGrrAIm9yeHxAFUbGvQ9.png')
                // — a diferencia de TicketAttachment (adjuntos de CLIENTE, ver
                // $customerFiles más abajo), aquí no se guardó nunca un
                // 'original_filename' en ningún sitio, así que no hay nombre
                // real que recuperar. Mostrar el hash crudo es ilegible; sin
                // inventar un nombre que no existe, se usa una etiqueta
                // genérica con quién lo adjuntó + su extensión real. Guardar
                // el nombre original a futuro para adjuntos de agente
                // requeriría tocar el esquema (columna nueva en TicketItem o
                // una tabla propia como TicketAttachment) — fuera de alcance
                // de este fix.
                'name' => 'Adjunto de '.$item->sender_name.(($ext = pathinfo((string) $path, PATHINFO_EXTENSION)) !== '' ? '.'.$ext : ''),
                'item_id' => $item->id,
                'source' => 'agent',
                'created_at_human' => $item->created_at?->diffForHumans(),
                'created_at' => $item->created_at?->toIso8601String(),
                // Descarga real (TicketAttachmentDownloadController) — el
                // índice es la posición dentro de attachment_urls del item,
                // que es como la ruta lo resuelve.
                'url_download' => route('manager.helpdesk.tickets.attachments.download', [$ticket, $item->id, $index]),
                'size' => Storage::disk($attachmentsDisk)->exists((string) $path)
                    ? Storage::disk($attachmentsDisk)->size((string) $path)
                    : null,
            ]));

        // Adjuntos subidos por el CLIENTE (portal, widget, formulario público):
        // TicketService::storeAttachments() los guarda como TicketAttachment
        // colgando de un TicketMessage. El panel no los leía en ningún sitio,
        // así que el agente nunca veía lo que mandaba el cliente. A diferencia
        // del array JSON, aquí sí tenemos nombre original, tamaño y mime
        // guardados en la propia fila.
        $customerFiles = TicketAttachment::query()
            ->whereHas('message', fn ($q) => $q->where('ticket_id', $ticket->id))
            ->with('message:id,ticket_id,created_at')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (TicketAttachment $a) => [
                'name' => $a->original_filename ?: $a->filename,
                'item_id' => null,
                'source' => 'customer',
                'created_at_human' => $a->created_at?->diffForHumans(),
                'created_at' => $a->created_at?->toIso8601String(),
                'url_download' => route('manager.helpdesk.tickets.message-attachments.download', [$ticket, $a->id]),
                'size' => $a->size,
            ]);

        $files = $itemFiles->concat($customerFiles)->sortByDesc('created_at')->values();

        // reorder() antes de latest(): Ticket::mails() ya define su propio
        // orderBy('created_at','asc') por defecto (para el hilo interno) —
        // encadenar latest() SIN limpiarlo antes añade un SEGUNDO
        // "ORDER BY created_at desc" sobre la MISMA columna, que MySQL
        // ignora (el primer criterio ya determina el orden por completo).
        // Sin este reorder(), $allMails/$lastMail salían en realidad en
        // ASC — el correo "más reciente" mostrado era el MÁS ANTIGUO del
        // ticket (bug real confirmado en vivo con TCK-2026-00014/173: el
        // widget "Último correo del ticket" mostraba un entrante de hace 3
        // días en vez del saliente de hoy).
        $allMails = $ticket->mails()->reorder()->latest()->limit(50)->get();
        $lastMail = $allMails->first();
        // Una sola consulta para mailOpensSummary()/mailClicksSummary() y
        // traceFor(): los tres cruzaban EmailLog por el mismo message_id por
        // separado. Ver EmailLogLookupService para por qué vive en un
        // servicio compartido en vez de repetirse aquí.
        $lastMailEmailLog = $lastMail ? $this->emailLogLookup->forMessageId($lastMail->message_id) : null;

        // "Reenviar último correo" SOLO puede operar sobre un correo de
        // SALIDA real (direction=outbound): si el más reciente del ticket es
        // uno de ENTRADA (recibido del cliente), su campo 'to' es la propia
        // bandeja de soporte (quien lo recibió, ver TicketMail::parseInbound*
        // en la ingesta), no la dirección del cliente — "reenviarlo" tal cual
        // generaría un correo real dirigido a soporte, el efecto opuesto al
        // que sugiere el botón (bug real de QA). Se busca aparte de $lastMail
        // (que sigue alimentando el widget puramente informativo "Último
        // correo del ticket": ese sí muestra el más reciente sea cual sea la
        // dirección). Consulta directa en vez de filtrar $allMails: el cap de
        // 50 de arriba podría no incluir el último saliente real si hay más
        // de 50 correos entrantes intercalados más recientes.
        $lastOutboundMail = $ticket->mails()->where('direction', 'outbound')->reorder()->latest()->first();

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
                // Bloque "Menciones" del panel de notas. La mención no se
                // guarda en ninguna columna: MentionService la deduce del
                // texto al notificar, así que aquí se usa ese mismo servicio
                // para que panel y notificación no puedan discrepar.
                'mentions' => $this->mentionService->resolveMentions((string) $n->body)
                    ->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => trim($u->firstname.' '.$u->lastname) ?: (string) $u->email,
                    ])->values()->all(),
                'created_at' => $n->created_at?->toIso8601String(),
                'created_at_human' => $n->created_at?->diffForHumans(),
            ])->values();

        return response()->json([
            'thread' => $thread,
            'activity' => $activity,
            // Ver comentario junto a $activityTotalCount: > count(activity)
            // cuando el limit(20) de arriba recortó historial real.
            'activity_total_count' => $activityTotalCount,
            'files' => $files,
            'mail' => $lastMail ? [
                'subject' => $lastMail->subject,
                'to' => $lastMail->to,
                'status' => $lastMail->status,
                'body_html' => $lastMail->safeBodyHtml(),
                'created_at_human' => $lastMail->created_at?->diffForHumans(),
                // Card "Destinatarios" del mockup (De / Para / CC / Responder
                // a) y su desplegable "Detalles técnicos". Todas estas
                // columnas ya existían en helpdesk_ticket_mails; simplemente
                // no se enviaban al front, que solo pintaba Para/Asunto.
                'from' => $lastMail->from,
                'cc' => $lastMail->cc,
                'bcc' => $lastMail->bcc,
                'message_id' => $lastMail->message_id,
                'in_reply_to' => $lastMail->in_reply_to,
                // body_text real del correo, no el body_html despojado de
                // etiquetas: la vista "Texto" del mockup enseña la parte
                // text/plain que se envió de verdad, que puede diferir.
                'body_text' => $lastMail->body_text,
                // "Fuente" (MIME crudo) solo si se archivó el correo entero.
                'raw_email' => $lastMail->raw_email,
                'attachments' => $lastMail->attachments ?? [],
                // Chip "Spam N/10": la puntuación que puso el filtro del
                // servidor entrante, leída de las cabeceras archivadas. null
                // cuando el correo no pasó por ningún filtro (ver
                // TicketMail::spamVerdict()).
                'spam' => $lastMail->spamVerdict(),
                // Modales 05 (detalle de entrega) y 06 (rebote).
                'id' => $lastMail->id,
                'direction' => $lastMail->direction,
                'sent_at_human' => $lastMail->sent_at?->translatedFormat('d M Y · H:i:s'),
                'delivered_at_human' => $lastMail->delivered_at?->translatedFormat('d M Y · H:i:s'),
                'delivery_error' => $lastMail->delivery_error,
                'url_resend' => route('manager.helpdesk.tickets.emails.resend', $lastMail),
                'url_fix_bounce' => route('manager.helpdesk.tickets.emails.fix-bounce', $lastMail),
                // Aperturas reales (EmailLogOpen, mismo cruce por message_id
                // que traceFor()) — "reintentos" del mockup se omite: no hay
                // columna de intentos en EmailLog, no se inventa.
                ...$this->mailOpensSummary($lastMail, $lastMailEmailLog),
                // Clics reales (EmailLogClick, vía EmailLogLink) — mismo
                // criterio que las aperturas.
                ...$this->mailClicksSummary($lastMail, $lastMailEmailLog),
            ] : null,
            // Datos de "Reenviar último correo" — deliberadamente
            // independientes del bloque 'mail' de arriba (ver comentario en
            // $lastOutboundMail). null cuando el ticket no tiene ningún
            // correo de SALIDA todavía (aunque sí tenga entrantes): el JS
            // debe deshabilitar el botón en ese caso con un motivo explícito
            // en vez de ofrecer "reenviar" un correo que en realidad llegó
            // del cliente.
            'last_outbound_mail' => $lastOutboundMail ? [
                'to' => $lastOutboundMail->to,
                'subject' => $lastOutboundMail->subject,
                // Reusa el mismo endpoint que ya existe en la bandeja de
                // emails (TicketMailsController::resend()) — sin duplicar
                // lógica de reenvío.
                'url_resend' => route('manager.helpdesk.tickets.emails.resend', $lastOutboundMail),
            ] : null,
            'trace' => $lastMail ? $this->traceFor($lastMail, $lastMailEmailLog) : [],
            // Bloques "Traza SMTP" e "Identificadores" del pie de la pestaña
            // Traza. El mockup enseña ahí relay/IP/TLS/reintentos, que esta
            // instalación NO guarda en ninguna parte (raw_headers está vacío
            // en las 1057 filas del log y ticket_mails no tiene columnas de
            // transporte): se publican solo los campos con dato real y el JS
            // omite las filas vacías, en vez de rellenarlas de ejemplo.
            'trace_meta' => $lastMail ? $this->traceMetaFor($ticket, $lastMail, $lastMailEmailLog) : null,
            // Lista completa (modal "Correos del ticket") — antes solo se
            // veía el último; el resto obligaba a salir a la bandeja global.
            'mails' => $allMails->map(fn ($m) => [
                'id' => $m->id,
                'subject' => $m->subject,
                'from' => $m->from,
                'to' => $m->to,
                'direction' => $m->direction,
                'status' => $m->status,
                'created_at_human' => $m->created_at?->diffForHumans(),
                // Modal 14: iniciales para el avatar de cada correo del hilo.
                'initials' => TicketMail::initialsFor($m->direction === 'inbound' ? $m->from : ($m->user?->fullName() ?: $m->from)),
                'attachment_count' => count($m->attachments ?? []),
                'scheduled_at_human' => $m->scheduled_at?->translatedFormat('d M · H:i'),
                // Modales 07/09/10, que actúan sobre un correo concreto.
                'url_resend' => route('manager.helpdesk.tickets.emails.resend', $m),
                'url_cancel_scheduled' => route('manager.helpdesk.tickets.emails.cancel-scheduled', $m),
                'url_link' => route('manager.helpdesk.tickets.emails.link', $m),
            ])->values()->all(),
            'customer' => $this->customerSummary->summarize($ticket->customer),
            // Modal 34: canales por los que escribe este contacto.
            'identities' => $this->customerSummary->identities($ticket->customer),
            'form' => $this->formDataFor($ticket),
            'notes' => $notes,
            'related' => $this->relatedTicketsFor($ticket),
            'side_conversations' => $this->sideConversationsFor($ticket),
            // Seguimientos/recordatorios reales (TicketFollowupsController,
            // ya con backend+comando programado helpdesk:send-due-ticket-followups
            // — solo faltaba exponerlos en esta pantalla; la ficha antigua
            // show.blade.php ya los muestra).
            'followups' => $ticket->followups
                ->sortBy('scheduled_at')
                ->map(fn ($f) => [
                    'id' => $f->id,
                    'step' => $f->step,
                    'scheduled_at' => $f->scheduled_at?->toIso8601String(),
                    'scheduled_at_human' => $f->scheduled_at?->format('d/m/Y H:i'),
                    'note' => $f->note,
                    // Un paso puede estar pendiente, ya avisado o cancelado
                    // porque el cliente respondió antes de que le tocara.
                    'state' => $f->cancelled_at ? 'cancelled' : ($f->is_sent ? 'sent' : 'pending'),
                    'cancel_if_customer_replies' => (bool) $f->cancel_if_customer_replies,
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
                // Qué avisos ha pedido ver cada seguidor. Hasta ahora seguir
                // un ticket no producía ningún aviso, así que tampoco había
                // preferencias que publicar.
                'notify_customer_replies' => (bool) $w->notify_customer_replies,
                'notify_internal_notes' => (bool) $w->notify_internal_notes,
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
            // Pestaña "Tickets del cliente" del panel derecho: el resto del
            // histórico del mismo contacto, para ver de un vistazo si lo que
            // pregunta ya se le respondió antes. Distinto de 'related', que
            // son los tickets ENLAZADOS a mano a éste (duplicado, bloquea…).
            'customer_tickets' => $this->customerTicketsFor($ticket),
            // Card "SLA" del panel derecho: barra de progreso + estado,
            // resolución y primera respuesta.
            'sla' => $this->slaSummary($ticket),
            // Bloque "Idioma y traducción" del panel Correo. Son ajustes
            // GLOBALES del módulo de traducción, no de este ticket: se
            // publican en solo lectura (con enlace a su pantalla) para que
            // el agente vea cómo va a salir la respuesta sin poder cambiar
            // la configuración de toda la empresa desde un ticket suelto.
            'translation' => $this->translationSettings(),
            // Card "Asignado a": los tres datos de la tablita (equipo,
            // seguidores, cuándo se asignó) y la carga del agente, que ya
            // calcula AssignmentService para el modal de reparto.
            'assignment' => [
                'group_name' => $ticket->group?->name,
                'watchers_count' => $ticket->watchers->count(),
                'assigned_at_human' => $ticket->assigned_at?->diffForHumans(),
                'agent_open_tickets' => $ticket->assignee_id
                    ? Ticket::query()
                        ->where('assignee_id', $ticket->assignee_id)
                        ->whereNull('closed_at')
                        ->count()
                    : null,
            ],
        ]);
    }

    /**
     * Histórico de tickets del mismo cliente, excluyendo el que se está
     * mirando. Limitado a 20: es un panel lateral de contexto, no un
     * listado — quien necesite más tiene el filtro por cliente del listado.
     *
     * @return array<int, array<string, mixed>>
     */
    private function customerTicketsFor(Ticket $ticket): array
    {
        if (! $ticket->customer_id) {
            return [];
        }

        return Ticket::query()
            ->where('customer_id', $ticket->customer_id)
            ->whereKeyNot($ticket->getKey())
            ->with(['status'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Ticket $other) => [
                'id' => $other->id,
                'ticket_number' => $other->ticket_number,
                'subject' => $other->subject ?? $other->title,
                'status_slug' => $other->statusSlug(),
                'status_name' => $other->status?->name,
                'priority' => $other->priority,
                'created_at_human' => $other->created_at?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * Estado de la traducción automática, si el módulo está activo.
     *
     * @return array{enabled: bool, incoming: bool, outgoing: bool, target: ?string, url_settings: ?string}|null
     */
    private function translationSettings(): ?array
    {
        if (! Module::find('HelpdeskTranslate')?->isEnabled()) {
            return null;
        }

        return [
            'enabled' => true,
            'incoming' => (bool) (Setting::get('helpdesktranslate.auto_translate_incoming')
                ?? config('helpdesktranslate.auto_translate_incoming', false)),
            'outgoing' => (bool) (Setting::get('helpdesktranslate.auto_translate_outgoing')
                ?? config('helpdesktranslate.auto_translate_outgoing', false)),
            'target' => Setting::get('helpdesktranslate.default_target')
                ?? config('helpdesktranslate.default_target'),
            'url_settings' => Route::has('settings.helpdesk-translate.index')
                ? route('settings.helpdesk-translate.index')
                : null,
        ];
    }

    /**
     * Resumen del SLA para el panel derecho. El porcentaje es el consumido
     * del plazo de RESOLUCIÓN (desde que se creó el ticket hasta que vence),
     * acotado a 0-100: es lo que la barra del mockup representa. Devuelve
     * null cuando el ticket no tiene ninguna política aplicada, en cuyo caso
     * la card no se pinta en vez de enseñar una barra vacía sin significado.
     *
     * @return array{state: string, label: string, percent: int|null, resolution: string, first_response: string}|null
     */
    private function slaSummary(Ticket $ticket): ?array
    {
        $due = $ticket->sla_resolution_due_at;
        $kind = $ticket->slaRowKind();

        if (! $due && ! $ticket->sla_first_response_due_at && ! $ticket->first_response_at) {
            return null;
        }

        $percent = null;
        if ($due && $ticket->created_at) {
            $total = $ticket->created_at->diffInSeconds($due, false);
            if ($total > 0) {
                $elapsed = $ticket->created_at->diffInSeconds(now(), false);
                $percent = (int) max(0, min(100, round($elapsed / $total * 100)));
            }
        }

        // "cumplida en 42 min": tiempo real que se tardó en dar la primera
        // respuesta, no el plazo que había.
        $firstResponse = '—';
        if ($ticket->first_response_at && $ticket->created_at) {
            $firstResponse = 'cumplida en '.$ticket->created_at->diffForHumans($ticket->first_response_at, [
                'syntax' => CarbonInterface::DIFF_ABSOLUTE,
                'parts' => 1,
            ]);
        } elseif ($ticket->sla_first_response_breached) {
            $firstResponse = 'incumplida';
        } elseif ($ticket->sla_first_response_due_at) {
            $firstResponse = 'pendiente';
        }

        return [
            'state' => $kind,
            'label' => match ($kind) {
                'breach' => 'Vencido',
                'warn' => 'En riesgo',
                default => 'En plazo',
            },
            'percent' => $percent,
            'resolution' => $ticket->slaRowText() ?: '—',
            'first_response' => $firstResponse,
        ];
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
     * Datos del formulario de origen.
     *
     * Contrato con dos estados distintos, a propósito (bug real de UI
     * encontrado en QA, TCK-2026-00009: el chip de origen bajo el título
     * decía "Formulario" mientras la pestaña Formulario decía "Este ticket
     * no proviene de un formulario", una contradicción directa porque antes
     * este método devolvía null tanto si el origen NO era un formulario
     * COMO si SÍ lo era pero custom_fields venía vacío — el JS no podía
     * distinguir ambos casos):
     *  - null puro: el ORIGEN del ticket no es un canal de formulario. El JS
     *    mantiene el mensaje actual ("Este ticket no proviene de un
     *    formulario. Origen: {label}.").
     *  - array con is_form_origin=true: el origen SÍ es un formulario.
     *    'fields' es el array de custom_fields capturado, o [] (nunca null)
     *    cuando el formulario no capturó ningún campo extra — el JS debe
     *    distinguir este caso con un mensaje honesto distinto ("Este ticket
     *    es de un formulario, pero no se capturaron campos adicionales."),
     *    no el mensaje de "no proviene de un formulario".
     */
    private function formDataFor(Ticket $ticket): ?array
    {
        $fields = $ticket->custom_fields ?: [];

        // Antes esto exigía que el origen fuese exactamente 'formulario' o
        // 'web_form'. En la base hay tickets con origen 'form' (que el resto
        // de la pantalla ya rotula como Formulario) y 16 con origen 'email'
        // que traen campos porque el formulario llega por correo: a todos
        // ellos el panel les respondía "este ticket no proviene de un
        // formulario" teniendo los campos guardados. El criterio pasa a ser
        // el dato: si hay campos capturados, se enseñan.
        if ($fields === []) {
            return null;
        }

        // Claves técnicas del envío frente a campos que rellenó el cliente.
        // El mockup separa "Campos enviados" de "Trazabilidad del envío";
        // aquí la separación es por nombre de clave, y la sección técnica
        // solo se emite si el formulario capturó alguno de esos datos —
        // ninguno de los formularios de esta instalación los guarda hoy, así
        // que lo normal es que salga vacía en vez de inventada.
        $traceKeys = [
            'form_key' => 'formulario',
            'page' => 'página',
            'page_url' => 'página',
            'url' => 'página',
            'referrer' => 'procedencia',
            'ip' => 'IP',
            'client_ip' => 'IP',
            'user_agent' => 'navegador',
            'utm_source' => 'campaña',
            'utm_medium' => 'medio',
            'utm_campaign' => 'campaña',
            'rgpd' => 'RGPD',
            'gdpr' => 'RGPD',
            'privacy_accepted' => 'RGPD',
            'consent' => 'RGPD',
            'submitted_at' => 'enviado',
            'store_url' => 'tienda',
        ];

        $submitted = [];
        $trace = [];
        // El texto libre se enseña como cita destacada arriba; repetirlo
        // otra vez dentro de la lista de campos era ruido.
        $freeText = $this->formFreeTextFor($fields);

        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_filter($value, 'is_scalar'));
            }

            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            $row = ['key' => $key, 'label' => $traceKeys[$key] ?? Str::headline((string) $key), 'value' => (string) $value];

            if (isset($traceKeys[$key])) {
                $trace[] = $row;
            } elseif ($freeText === null || trim((string) $value) !== $freeText) {
                $submitted[] = $row;
            }
        }

        // Adjuntos que llegaron con el formulario: son los del cliente, ya
        // cargados como TicketAttachment (mismo origen que $customerFiles).
        $attachments = $ticket->items
            ->flatMap(fn (TicketItem $item) => $item->attachments ?? collect())
            ->map(fn ($a) => [
                'name' => $a->original_filename ?: $a->filename,
                'size_human' => $this->humanSize((int) $a->size),
                'url_download' => route('manager.helpdesk.tickets.message-attachments.download', [$ticket, $a->id]),
            ])->values()->all();

        return [
            'is_form_origin' => in_array($ticket->sourceSlug(), ['formulario', 'form', 'web_form'], true),
            'source_label' => $ticket->sourceSlug(),
            // Cita destacada del mockup: lo que el cliente escribió de su
            // puño, separado de los campos con valor de lista.
            'message' => $freeText,
            'fields' => $fields,
            'submitted' => $submitted,
            'trace' => $trace,
            'attachments' => $attachments,
        ];
    }

    /**
     * Campo de texto libre de un formulario (el "mensaje"), si lo hay.
     *
     * @param  array<string, mixed>  $fields
     */
    private function formFreeTextFor(array $fields): ?string
    {
        foreach (['message', 'mensaje', 'comment', 'comments', 'comentario', 'comentarios',
            'observations', 'observaciones', 'consulta', 'descripcion', 'description',
            'body', 'texto', 'pregunta'] as $key) {
            $value = $fields[$key] ?? null;

            if (is_string($value) && mb_strlen(trim($value)) >= 20) {
                return trim($value);
            }
        }

        return null;
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
     *
     * @return array{opens_count: int, last_opened_human: ?string}
     */
    private function mailOpensSummary(TicketMail $mail, ?EmailLog $log): array
    {
        return $this->engagementSummary($mail, $log?->opens, 'opened_at', 'opens_count', 'last_opened_human');
    }

    /**
     * Mismo criterio que mailOpensSummary(), pero sobre los clics de los
     * enlaces reescritos del correo (ver HelpdeskEmailActivity\Models\EmailLog::
     * clicks(), hasManyThrough vía EmailLogLink).
     *
     * @return array{clicks_count: int, last_clicked_human: ?string}
     */
    private function mailClicksSummary(TicketMail $mail, ?EmailLog $log): array
    {
        return $this->engagementSummary($mail, $log?->clicks, 'clicked_at', 'clicks_count', 'last_clicked_human');
    }

    /**
     * Forma común de mailOpensSummary()/mailClicksSummary(): contar una
     * colección de eventos (EmailLogOpen o EmailLogClick) y devolver el más
     * reciente en humano — la única diferencia real entre aperturas y clics
     * es QUÉ colección y QUÉ columna de fecha, nunca la forma del resultado.
     * Los nombres de las claves de salida se pasan explícitos (en vez de
     * derivarlos de $dateField) porque el JS (renderCorreoSidePane en
     * tickets-app.js) ya los lee tal cual.
     *
     * @param  ?Collection<int, mixed>  $events
     */
    private function engagementSummary(TicketMail $mail, ?Collection $events, string $dateField, string $countKey, string $lastHumanKey): array
    {
        if (! $mail->message_id) {
            return [$countKey => 0, $lastHumanKey => null];
        }

        $last = $events?->sortByDesc($dateField)->first();

        return [
            $countKey => $events?->count() ?? 0,
            $lastHumanKey => $last?->{$dateField}?->diffForHumans(),
        ];
    }

    /**
     * Nombre visible del autor de una entrada de historial.
     */
    private function causerName(?object $causer): ?string
    {
        if (! $causer) {
            return null;
        }

        if (method_exists($causer, 'fullName') && ($full = trim((string) $causer->fullName())) !== '') {
            return $full;
        }

        $name = trim(($causer->firstname ?? '').' '.($causer->lastname ?? ''));

        return $name !== '' ? $name : ($causer->email ?? null);
    }

    /**
     * Quién provocó el cambio, para la etiqueta del mockup: un agente, el
     * propio cliente, una automatización o el sistema. Sin causer registrado
     * la entrada la escribió un proceso automático, no una persona.
     */
    private function causerKind(object $activity): string
    {
        if ($activity->causer) {
            return 'agent';
        }

        return str_contains(mb_strtolower((string) $activity->description), 'cliente')
            ? 'customer'
            : 'system';
    }

    /**
     * Datos de transporte e identificadores del último correo del ticket.
     *
     * @return array{smtp: array<int, array{k: string, v: string}>, ids: array<int, array{k: string, v: string}>}
     */
    private function traceMetaFor(Ticket $ticket, TicketMail $mail, ?EmailLog $log): array
    {
        $smtp = [];

        // Latencia real de entrega: lo que tardó el destino en confirmar
        // desde que el servidor aceptó el mensaje.
        $sentAt = $log?->sent_at ?? $mail->sent_at;
        if ($sentAt && $mail->delivered_at) {
            $seconds = $sentAt->diffInSeconds($mail->delivered_at, false);
            if ($seconds >= 0) {
                $smtp[] = ['k' => 'latencia', 'v' => $seconds < 60
                    ? number_format($seconds, 1, ',', '.').' s'
                    : $sentAt->diffForHumans($mail->delivered_at, ['syntax' => CarbonInterface::DIFF_ABSOLUTE, 'parts' => 1])];
            }
        }

        if ($mail->to && str_contains($mail->to, '@')) {
            $smtp[] = ['k' => 'dominio destino', 'v' => Str::afterLast($mail->to, '@')];
        }

        // Transporte configurado en la app: es de dónde salió el correo.
        if ($mailer = config('mail.default')) {
            $transport = config('mail.mailers.'.$mailer.'.transport', $mailer);
            $smtp[] = ['k' => 'transporte', 'v' => $transport === $mailer ? (string) $mailer : $mailer.' · '.$transport];
        }

        if ($host = config('mail.mailers.'.config('mail.default').'.host')) {
            $smtp[] = ['k' => 'host', 'v' => (string) $host];
        }

        $ids = [
            ['k' => 'mail_id', 'v' => (string) $mail->id],
            ['k' => 'ticket_id', 'v' => (string) $ticket->id],
            ['k' => 'dirección', 'v' => $mail->direction === 'inbound' ? 'entrante' : 'saliente'],
            ['k' => 'estado', 'v' => (string) ($mail->status ?: '—')],
        ];

        if ($mail->message_id) {
            $ids[] = ['k' => 'message_id', 'v' => trim($mail->message_id, '<>')];
        }

        // El log solo existe si el mailable usa TracksEmailLog; cuando está,
        // su uid es la forma de saltar al registro completo del envío.
        if ($log) {
            $ids[] = ['k' => 'email_log', 'v' => (string) $log->uid];
            if ($log->external_id) {
                $ids[] = ['k' => 'id del proveedor', 'v' => (string) $log->external_id];
            }
        }

        return ['smtp' => $smtp, 'ids' => $ids];
    }

    /**
     * Cada evento lleva 'at' (ISO-8601 crudo, se conserva por si algún
     * consumidor futuro necesita ordenar/comparar fechas) Y 'at_human'
     * (diffForHumans, mismo patrón created_at/created_at_human que ya usan
     * Hilo/Actividad/Correo/Archivos en este mismo controlador) — antes solo
     * existía 'at' crudo y el JS lo imprimía tal cual ('2026-08-28T14:06:19
     * +00:00'), inconsistente con el resto de la pantalla.
     */
    private function traceFor(TicketMail $mail, ?EmailLog $log): array
    {
        // Antes esto exigía un EmailLog correlacionado y devolvía [] si no
        // lo había: la pestaña Traza se quedaba en blanco aunque el propio
        // TicketMail tuviera sent_at/delivered_at. No todos los correos de
        // ticket pasan por el módulo de log (solo los mailables que usan
        // TracksEmailLog), así que se construye la traza con lo que hay: el
        // log cuando existe, y el TicketMail como fuente en cualquier caso.
        if (! $log && ! $mail->sent_at && ! $mail->delivered_at && ! $mail->delivery_error) {
            return [];
        }

        // 'detail' es la segunda línea de cada hito en la pestaña Traza: el
        // dato concreto que explica el paso (el mailable que lo encoló, el
        // error que devolvió el servidor…). Vacío cuando no hay nada real que
        // contar — no se rellena con texto de ejemplo.
        $events = [
            [
                'type' => 'queued',
                'label' => 'Encolado para envío',
                'detail' => $mail->headers['Mailable'] ?? ($log?->mailable_class),
                'at' => ($log?->created_at ?? $mail->created_at)?->toIso8601String(),
                'at_human' => ($log?->created_at ?? $mail->created_at)?->diffForHumans(),
            ],
        ];

        if ($sentAt = ($log?->sent_at ?? $mail->sent_at)) {
            $events[] = ['type' => 'sent', 'label' => 'Aceptado por el servidor de correo', 'detail' => $mail->from ? 'desde '.$mail->from : null, 'at' => $sentAt->toIso8601String(), 'at_human' => $sentAt->diffForHumans()];
        }
        if ($mail->delivered_at) {
            $events[] = ['type' => 'delivered', 'label' => 'Entregado al destinatario', 'detail' => $mail->to, 'at' => $mail->delivered_at->toIso8601String(), 'at_human' => $mail->delivered_at->diffForHumans()];
        }
        if ($log?->bounced_at) {
            $events[] = ['type' => 'bounced', 'label' => 'Rebotado', 'detail' => $mail->delivery_error, 'at' => $log->bounced_at->toIso8601String(), 'at_human' => $log->bounced_at->diffForHumans()];
        }
        if ($failedAt = ($log?->failed_at ?? ($mail->status === 'failed' ? $mail->updated_at : null))) {
            $events[] = ['type' => 'failed', 'label' => 'Fallido', 'detail' => $mail->delivery_error, 'at' => $failedAt->toIso8601String(), 'at_human' => $failedAt->diffForHumans()];
        }
        // Misma forma para aperturas y clics (un evento por hit, sin
        // agregar) — solo cambia qué colección y qué columna de fecha leer.
        foreach ([
            ['items' => $log?->opens ?? [], 'field' => 'opened_at', 'type' => 'opened', 'label' => 'Abierto por el destinatario'],
            ['items' => $log?->clicks ?? [], 'field' => 'clicked_at', 'type' => 'clicked', 'label' => 'Enlace clicado por el destinatario'],
        ] as $group) {
            foreach ($group['items'] as $item) {
                $at = $item->{$group['field']};
                $events[] = [
                    'type' => $group['type'],
                    'label' => $group['label'],
                    // El user agent es lo único que el log guarda de cada
                    // apertura/clic; se recorta porque son cadenas larguísimas.
                    'detail' => isset($item->user_agent) ? Str::limit((string) $item->user_agent, 60) : null,
                    'at' => $at?->toIso8601String(),
                    'at_human' => $at?->diffForHumans(),
                ];
            }
        }

        return $events;
    }
}

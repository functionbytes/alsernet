<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Services\CustomerSummaryService;
use Modules\HelpdeskTickets\Services\HelpdeskTicketBridgeService;

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
    ) {}

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

        // Adjuntos escritos por el panel de agente: rutas de storage dentro de
        // TicketItem.attachment_urls.
        $itemFiles = $ticket->items
            ->filter(fn ($item) => $item->hasAttachments())
            ->flatMap(fn ($item) => collect($item->attachment_urls)->values()->map(fn ($path, $index) => [
                'name' => basename((string) $path),
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

        $allMails = $ticket->mails()->latest()->limit(50)->get();
        $lastMail = $allMails->first();
        // Una sola consulta para mailOpensSummary() y traceFor(): ambos
        // cruzaban EmailLog por el mismo message_id por separado.
        $lastMailEmailLog = ($lastMail && $lastMail->message_id)
            ? EmailLog::with('opens')->where('message_id', trim($lastMail->message_id, '<>'))->first()
            : null;

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
                ...$this->mailOpensSummary($lastMail, $lastMailEmailLog),
            ] : null,
            'trace' => $lastMail ? $this->traceFor($lastMail, $lastMailEmailLog) : [],
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
            'customer' => $this->customerSummary->summarize($ticket->customer),
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
     *
     * @return array{opens_count: int, last_opened_human: ?string}
     */
    private function mailOpensSummary(TicketMail $mail, ?EmailLog $log): array
    {
        if (! $mail->message_id) {
            return ['opens_count' => 0, 'last_opened_human' => null];
        }

        $lastOpen = $log?->opens->sortByDesc('opened_at')->first();

        return [
            'opens_count' => $log?->opens->count() ?? 0,
            'last_opened_human' => $lastOpen?->opened_at?->diffForHumans(),
        ];
    }

    private function traceFor(TicketMail $mail, ?EmailLog $log): array
    {
        if (! $mail->message_id || ! $log) {
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
}

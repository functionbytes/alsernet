<?php

namespace Modules\HelpdeskTickets\Services;

use Modules\Core\Models\Setting;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\TicketDetail\ActivityBuilder;
use Modules\HelpdeskTickets\Services\TicketDetail\FilesBuilder;
use Modules\HelpdeskTickets\Services\TicketDetail\FormBuilder;
use Modules\HelpdeskTickets\Services\TicketDetail\MailBuilder;
use Modules\HelpdeskTickets\Services\TicketDetail\NotesBuilder;
use Modules\HelpdeskTickets\Services\TicketDetail\RelatedBuilder;
use Modules\HelpdeskTickets\Services\TicketDetail\SidebarBuilder;
use Modules\HelpdeskTickets\Services\TicketDetail\Support\FormatsDisplayTimezone;
use Modules\HelpdeskTickets\Services\TicketDetail\ThreadBuilder;
use Modules\HelpdeskTickets\Services\TicketDetail\WorkBuilder;

/**
 * Ensamblado del JSON del panel de detalle del ticket (Hilo, actividad,
 * correo, notas, relacionados, etc.), extraído de TicketDetailDataController
 * (30-sep-2026, controller de 1502 líneas) para que la clase HTTP se limite a
 * autorizar y despachar. Sin cambios de comportamiento, solo movimiento — ver
 * el docblock de TicketDetailDataController para el porqué original de
 * separarlo de TicketsCrudController.
 *
 * Trocado (30-sep-2026, este servicio llegó a 1461 líneas) en un
 * colaborador por sección de panel bajo Services/TicketDetail/ — esta clase
 * queda como façade: orquesta el orden real de las operaciones (el hilo se
 * pagina antes de marcar leído, que a su vez debe ir antes de leer el
 * historial de actividad para que "Ticket visto" salga en él) y ensambla el
 * array final. CustomerSummaryService/EmailLogLookupService/MentionService
 * no se tocan aquí más que para inyectarlos donde ya vivían.
 */
class TicketDetailDataService
{
    use FormatsDisplayTimezone;

    public function __construct(
        private readonly CustomerSummaryService $customerSummary,
        private readonly ThreadBuilder $threadBuilder,
        private readonly ActivityBuilder $activityBuilder,
        private readonly FilesBuilder $filesBuilder,
        private readonly MailBuilder $mailBuilder,
        private readonly NotesBuilder $notesBuilder,
        private readonly RelatedBuilder $relatedBuilder,
        private readonly FormBuilder $formBuilder,
        private readonly WorkBuilder $workBuilder,
        private readonly SidebarBuilder $sidebarBuilder,
    ) {}

    /**
     * Ensambla el payload completo del panel de detalle. Llamado por
     * TicketDetailDataController::data() DESPUÉS de autorizar la vista del
     * ticket — este método asume que el caller ya lo comprobó.
     */
    public function build(Ticket $ticket): array
    {
        $pagination = $this->threadBuilder->paginate($ticket);

        // El resto del payload puede seguir usando $ticket->items para el
        // hilo de esta página. Las pestañas de archivos consultan sus propios
        // items con adjuntos más abajo (FilesBuilder), por lo que no pierden
        // ficheros de páginas que todavía no se han cargado.
        $ticket->setRelation('items', $pagination['items']);
        $ticket->load(['followups', 'aiSuggestedCategory', 'watchers.user']);

        // Debe ir ANTES de $this->activityBuilder->build(): "Ticket visto"
        // solo aparece en el historial que se lee justo después si ya se
        // escribió aquí.
        $this->activityBuilder->recordView($ticket);

        $thread = $this->threadBuilder->mapItems($ticket, $pagination['items'], $pagination['synthetic_first'] ?? null);
        $activity = $this->activityBuilder->build($ticket);
        $mail = $this->mailBuilder->build($ticket);

        return [
            'thread' => $thread,
            'activity' => $activity['activity'],
            'activity_total_count' => $activity['activity_total_count'],
            'thread_search' => $pagination['search'],
            'thread_page' => $pagination['page'],
            'thread_per_page' => $pagination['per_page'],
            'thread_total' => $pagination['total'],
            'thread_has_more' => $pagination['has_more'],
            'thread_filters' => $pagination['filters'],
            'files' => $this->filesBuilder->build($ticket),
            'mail' => $mail['mail'],
            'last_outbound_mail' => $mail['last_outbound_mail'],
            'trace' => $mail['trace'],
            'trace_meta' => $mail['trace_meta'],
            'mails' => $mail['mails'],
            'customer' => $this->customerSummary->summarize($ticket->customer),
            // Modal 34: canales por los que escribe este contacto.
            'identities' => $this->customerSummary->identities($ticket->customer),
            'form' => $this->formBuilder->build($ticket),
            'notes' => $this->notesBuilder->build($ticket),
            'related' => $this->relatedBuilder->relatedTicketsFor($ticket),
            'side_conversations' => $this->relatedBuilder->sideConversationsFor($ticket),
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
                    'scheduled_at_human' => $this->inDisplayTz($f->scheduled_at)?->format('d/m/Y H:i'),
                    'note' => $f->note,
                    // Un paso puede estar pendiente, ya avisado o cancelado
                    // porque el cliente respondió antes de que le tocara.
                    'state' => $f->cancelled_at ? 'cancelled' : ($f->is_sent ? 'sent' : 'pending'),
                    'cancel_if_customer_replies' => (bool) $f->cancel_if_customer_replies,
                ])->values()->all(),
            // Sugerencias de IA ya calculadas (TicketAiService) — modal 27
            // "Etiquetado automático": si no hay sugerencia real, se omite
            // el bloque entero. La confianza es la cuota real de coincidencia
            // de palabras clave (ver TicketAiService::suggestCategory()), no
            // la probabilidad de un modelo entrenado.
            'ai_suggestion' => ($ticket->aiSuggestedCategory || $ticket->ai_suggested_priority) ? [
                'category' => $ticket->aiSuggestedCategory ? [
                    'id' => $ticket->aiSuggestedCategory->id,
                    'name' => $ticket->aiSuggestedCategory->name,
                    'confidence' => $ticket->ai_suggested_category_confidence !== null ? (float) $ticket->ai_suggested_category_confidence : null,
                ] : null,
                'priority' => $ticket->ai_suggested_priority,
                'priority_confidence' => $ticket->ai_suggested_priority_confidence !== null ? (float) $ticket->ai_suggested_priority_confidence : null,
            ] : null,
            // Checkbox del modal 27: interruptor global, no por ticket — se
            // manda aquí porque el modal ya carga este JSON, sin pedirlo aparte.
            'ai_auto_apply_high_confidence' => filter_var(Setting::get('tickets.ai_auto_apply_high_confidence', false), FILTER_VALIDATE_BOOLEAN),
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
            'customer_tickets' => $this->relatedBuilder->customerTicketsFor($ticket),
            // Card "SLA" del panel derecho: barra de progreso + estado,
            // resolución y primera respuesta.
            'sla' => $this->sidebarBuilder->slaSummary($ticket),
            // Bloque "Idioma y traducción" del panel Correo. Son ajustes
            // GLOBALES del módulo de traducción, no de este ticket: se
            // publican en solo lectura (con enlace a su pantalla) para que
            // el agente vea cómo va a salir la respuesta sin poder cambiar
            // la configuración de toda la empresa desde un ticket suelto.
            'translation' => $this->sidebarBuilder->translationSettings(),
            // Card "Asignado a": los tres datos de la tablita (equipo,
            // seguidores, cuándo se asignó) y la carga del agente, que ya
            // calcula AssignmentService para el modal de reparto.
            // Borradores en servidor: el mío completo (para recuperarlo en otro
            // equipo) y, de los demás, solo quién y cuándo — nunca el texto.
            ...$this->workBuilder->draftsFor($ticket),
            // Checklist interna, subtickets y ticket padre.
            'work' => $this->workBuilder->workFor($ticket),
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
                // Estado real del agente (heartbeat + estado elegido). Antes
                // el panel pintaba "en línea" fijo para cualquier asignado.
                'agent_presence' => $this->sidebarBuilder->agentPresence($ticket->assignee_id),
            ],
        ];
    }
}

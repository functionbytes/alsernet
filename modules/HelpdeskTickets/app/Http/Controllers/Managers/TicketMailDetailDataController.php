<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\CustomerSummaryService;
use Modules\HelpdeskTickets\Services\EmailLogLookupService;
use Modules\HelpdeskTickets\Services\HelpdeskTicketBridgeService;

/**
 * Extraído de TicketMailsController (30-ago-2026, controller de 829 líneas)
 * — el endpoint data() y sus helpers privados construyen el JSON del panel
 * lateral de un email seleccionado en la bandeja (ticket, cliente,
 * trazabilidad, actividad, relacionados). Sin cambios de comportamiento,
 * solo movimiento — mapCustomer()/contactStats() ahora viven en
 * CustomerSummaryService (antes duplicados con TicketDetailDataController).
 *
 * ADVERTENCIA (1-sep-2026): no se encontró ningún consumidor real de esta
 * ruta en tickets-app.js — fetchDetailData() usa t.url_data (el endpoint de
 * TicketDetailDataController, con SU PROPIO 'mail'/'trace' de nivel
 * superior), y el propio docblock de TicketMailsController enumera "modal
 * de Entregabilidad" y "refetch de stats" como los DOS únicos usos vivos de
 * la API JSON de esa pantalla — este endpoint no es ninguno de los dos.
 * Sigue vivo (ruta + 2 test files verificando el contrato) pero es
 * candidato fuerte a código muerto; se deja intacto y sincronizado con
 * TicketDetailDataController por si acaso, no se elimina sin confirmar con
 * el equipo que de verdad nada lo llama.
 */
class TicketMailDetailDataController extends Controller
{
    public function __construct(
        private readonly CustomerSummaryService $customerSummary,
        private readonly EmailLogLookupService $emailLogLookup,
    ) {}

    public function data(TicketMail $mail): JsonResponse
    {
        $this->authorize('view', $mail);

        $mail->load(['ticket.customer', 'ticket.status', 'ticket.category', 'ticket.assignee', 'user', 'category']);

        // toListRow() lee ticket->customer, category y user por cada mensaje
        // del hilo — sin eager load esto eran 3-4 queries extra por email
        // (120-180 en un hilo largo). Tope de 100 porque el panel solo
        // muestra un hilo, no un histórico completo.
        $thread = TicketMail::where('ticket_id', $mail->ticket_id)
            ->with(['ticket.customer', 'user:id,firstname,lastname', 'category:id,name'])
            ->oldest()
            ->limit(100)
            ->get();

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

        $log = $this->emailLogLookup->forMessageId($mail->message_id);

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

        $clicks = $log->clicks;
        if ($clicks->isNotEmpty()) {
            $last = $clicks->max('clicked_at');
            $events[] = [
                'type' => 'clicked',
                'label' => 'Enlace clicado por el destinatario · '.$clicks->count().' '.($clicks->count() === 1 ? 'vez' : 'veces'),
                'detail' => 'último '.$last->format('H:i'),
                'at' => $clicks->min('clicked_at')?->toIso8601String(),
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
                'url_full' => route('manager.helpdesk.tickets.show', $t),
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
            'customer' => $this->customerSummary->summarize($ticket->customer),
            'assignee' => $ticket->assignee ? trim("{$ticket->assignee->firstname} {$ticket->assignee->lastname}") : null,
            'category' => $ticket->category?->name,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'created_at_human' => $ticket->created_at?->format('Y-m-d H:i'),
            'updated_at_human' => $ticket->updated_at?->diffForHumans(),
            'tags' => $ticket->tags ?? [],
            'source' => $ticket->source,
            'sla' => $this->mapSla($ticket),
            'url_full' => route('manager.helpdesk.tickets.show', $ticket),
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
}

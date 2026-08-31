<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\CustomerSummaryService;
use Modules\HelpdeskTickets\Services\HelpdeskTicketBridgeService;

/**
 * Extraído de TicketMailsController (30-ago-2026, controller de 829 líneas)
 * — el endpoint data() y sus helpers privados construyen el JSON del panel
 * lateral de un email seleccionado en la bandeja (ticket, cliente,
 * trazabilidad, actividad, relacionados). Sin cambios de comportamiento,
 * solo movimiento — mapCustomer()/contactStats() ahora viven en
 * CustomerSummaryService (antes duplicados con TicketDetailDataController).
 */
class TicketMailDetailDataController extends Controller
{
    public function __construct(
        private readonly CustomerSummaryService $customerSummary,
    ) {}

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
            'customer' => $this->customerSummary->summarize($ticket->customer),
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

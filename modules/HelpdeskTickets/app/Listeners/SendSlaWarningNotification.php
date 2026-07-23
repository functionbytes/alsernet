<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\SlaWarning;
use Modules\HelpdeskTickets\Mail\SlaWarningMail;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;

/**
 * Send warning notification when ticket SLA is approaching breach
 */
class SendSlaWarningNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct()
    {
        $this->queue = 'notifications';
    }

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    /**
     * Handle the event
     */
    public function handle(SlaWarning $event): void
    {
        $ticket = $event->ticket;

        if (! $ticket->assignedAgent) {
            Log::warning('Cannot send SLA warning - no agent assigned', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        $agent = $ticket->assignedAgent;
        $timeRemaining = now()->diff($ticket->due_at);

        Log::info('Sending SLA warning notification', [
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'due_at' => $ticket->due_at,
            'time_remaining' => $timeRemaining->format('%h horas %i minutos'),
        ]);

        try {
            [$subject, $content] = TicketMailRenderer::render(
                'helpdesk_tickets.sla_warning',
                [
                    'PERCENT_USED' => (string) round($event->percentUsed ?? 0),
                    'TICKET_NUMBER' => $ticket->ticket_number,
                    'TICKET_SUBJECT' => $ticket->subject,
                    'CUSTOMER_NAME' => $ticket->customer->name ?? 'N/A',
                    'DUE_AT' => $ticket->sla_resolution_due_at?->format('M d, Y H:i') ?? 'N/A',
                ],
                'SLA Warning — Ticket #'.$ticket->ticket_number.' ('.round($event->percentUsed ?? 0).'% used)',
            );

            Mail::to($agent->email, $agent->name)->queue(new SlaWarningMail($ticket, $subject, $content));
        } catch (\Throwable $e) {
            Log::error('Helpdesk notification failed', [
                'listener' => static::class,
                'agent_id' => $agent->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(SlaWarning $event, \Throwable $exception): void
    {
        Log::error('SendSlaWarningNotification listener failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

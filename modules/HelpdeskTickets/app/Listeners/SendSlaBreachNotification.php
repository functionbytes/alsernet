<?php

namespace Modules\HelpdeskTickets\Listeners;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Mail\SlaBreachMail;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;

/**
 * Send notification when ticket SLA is breached
 */
class SendSlaBreachNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    /**
     * La cola va en viaQueue() y no en el constructor: el Dispatcher lee las
     * opciones del listener sobre una instancia creada SIN constructor, así
     * que un $this->queue de ahí nunca se aplicaba y el job caía en
     * 'default' — cola que ningún worker atiende. Ver SendCustomerConfirmation.
     */
    public function viaQueue(): string
    {
        return 'notifications';
    }

    /**
     * Handle the event
     */
    public function handle(SlaBreached $event): void
    {
        $ticket = $event->ticket;
        $timeExceeded = now()->diff($ticket->sla_resolution_due_at ?? now());

        Log::info('Sending SLA breach notifications', [
            'ticket_id' => $ticket->id,
            'due_at' => $ticket->sla_resolution_due_at,
            'time_exceeded' => $timeExceeded->format('%h horas %i minutos'),
        ]);

        $recipients = [];

        if ($ticket->assignee) {
            $recipients[] = $ticket->assignee;
        }

        // Con el resumen activo (sla_alerts.managers_digest) los managers lo
        // ven en ticket:sla-digest, salvo que no haya agente al que avisar.
        $managers = config('helpdesktickets.sla_alerts.managers_digest', true) && $recipients !== []
            ? collect()
            : User::permission('manage_helpdesk')->get();
        foreach ($managers as $manager) {
            if (! in_array($manager->id, array_column($recipients, 'id'))) {
                $recipients[] = $manager;
            }
        }

        [$subject, $content] = TicketMailRenderer::render(
            'helpdesk_tickets.sla_breach',
            [
                'TICKET_NUMBER' => $ticket->ticket_number,
                'TICKET_SUBJECT' => $ticket->subject,
                'CUSTOMER_NAME' => $ticket->customer->name ?? 'N/A',
                'DUE_AT' => $ticket->sla_resolution_due_at?->format('M d, Y H:i') ?? 'N/A',
            ],
            'SLA Breach Alert — Ticket #'.$ticket->ticket_number,
        );

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email, $recipient->full_name)->queue(new SlaBreachMail($ticket, $subject, $content));

                Log::info('SLA breach notification sent', [
                    'ticket_id' => $ticket->id,
                    'recipient_id' => $recipient->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Helpdesk notification failed', [
                    'listener' => static::class,
                    'agent_id' => $recipient->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(SlaBreached $event, \Throwable $exception): void
    {
        Log::error('SendSlaBreachNotification listener failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

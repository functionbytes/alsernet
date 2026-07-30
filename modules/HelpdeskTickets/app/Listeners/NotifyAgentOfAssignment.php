<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Mail\TicketAssignedMail;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;

class NotifyAgentOfAssignment implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public function __construct()
    {
        $this->queue = 'notifications';
    }

    public function handle(TicketAssigned $event): void
    {
        $ticket = $event->ticket;
        $agent = $event->agent;

        Log::info('Notifying agent of ticket assignment', [
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'agent_email' => $agent->email,
        ]);

        [$subject, $content] = TicketMailRenderer::render(
            'helpdesk_tickets.ticket_assigned',
            [
                'AGENT_NAME' => e($agent->name),
                'TICKET_NUMBER' => $ticket->ticket_number,
                'TICKET_SUBJECT' => e($ticket->subject),
                'CUSTOMER_NAME' => e($ticket->customer->name ?? 'N/A'),
                'CATEGORY' => e($ticket->category->name ?? 'N/A'),
                'PRIORITY' => ucfirst($ticket->priority ?? 'normal'),
                'TICKET_URL' => url('/helpdesk/agent/tickets/'.$ticket->ticket_number),
            ],
            'Ticket assigned to you — #'.$ticket->ticket_number,
        );

        Mail::to($agent->email, $agent->name)->queue(new TicketAssignedMail($ticket, $subject, $content));
    }

    public function failed(TicketAssigned $event, \Throwable $exception): void
    {
        Log::error('NotifyAgentOfAssignment listener failed', [
            'ticket_id' => $event->ticket->id,
            'agent_id' => $event->agent->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

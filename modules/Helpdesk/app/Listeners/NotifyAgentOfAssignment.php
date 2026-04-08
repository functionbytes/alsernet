<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Events\TicketAssigned;
use Modules\Helpdesk\Mail\TicketAssignedMail;

/**
 * Notify agent when a ticket is assigned to them
 */
class NotifyAgentOfAssignment implements ShouldQueue
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
    public function handle(TicketAssigned $event): void
    {
        $ticket = $event->ticket;
        $agent = $event->agent;

        Log::info('Notifying agent of ticket assignment', [
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'agent_email' => $agent->email,
        ]);

        Mail::to($agent->email, $agent->name)->queue(new TicketAssignedMail($ticket, $agent));
    }
}

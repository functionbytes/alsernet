<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Mail\TicketAssignedMail;

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

        Mail::to($agent->email, $agent->name)->queue(new TicketAssignedMail($ticket, $agent));
    }
}

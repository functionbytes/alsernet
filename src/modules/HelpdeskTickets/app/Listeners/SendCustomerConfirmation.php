<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Mail\TicketCreatedMail;

/**
 * Send confirmation email to customer when ticket is created
 */
class SendCustomerConfirmation implements ShouldQueue
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
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        Log::info('Sending customer confirmation email', [
            'ticket_id' => $ticket->id,
            'customer_email' => $ticket->customer_email,
        ]);

        Mail::to($ticket->customer_email, $ticket->customer_name)->queue(new TicketCreatedMail($ticket));
    }
}

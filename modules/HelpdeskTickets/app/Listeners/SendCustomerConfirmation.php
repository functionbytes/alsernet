<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Mail\TicketCreatedMail;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;

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
        $customerEmail = $ticket->customer?->email;

        if (! $customerEmail) {
            Log::info('Skipping customer confirmation email: ticket has no customer', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        Log::info('Sending customer confirmation email', [
            'ticket_id' => $ticket->id,
            'customer_email' => $customerEmail,
        ]);

        [$subject, $content] = TicketMailRenderer::render(
            'helpdesk_tickets.ticket_created',
            [
                'TICKET_NUMBER' => $ticket->ticket_number,
                'TICKET_SUBJECT' => $ticket->subject,
                'SUBMITTED_AT' => $ticket->created_at->format('M d, Y H:i'),
            ],
            'Your ticket has been received — #'.$ticket->ticket_number,
        );

        Mail::to($customerEmail, $ticket->customer?->name)->queue(new TicketCreatedMail($ticket, $subject, $content));
    }

    public function failed(TicketCreated $event, \Throwable $exception): void
    {
        Log::error('SendCustomerConfirmation listener failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

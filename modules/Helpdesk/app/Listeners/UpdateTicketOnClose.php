<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Events\TicketClosed;
use Modules\Helpdesk\Mail\TicketSatisfactionSurveyMail;

/**
 * Update ticket data and notify customer when ticket is closed
 */
class UpdateTicketOnClose implements ShouldQueue
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
    public function handle(TicketClosed $event): void
    {
        $ticket = $event->ticket;

        // Ensure closed_at is set (may have been set already by Ticket::close())
        if (! $ticket->closed_at) {
            $ticket->updateQuietly(['closed_at' => now()]);
        }

        Log::info('Ticket closed — processing notifications', [
            'ticket_id' => $ticket->id,
            'closed_at' => $ticket->closed_at,
        ]);

        // Send satisfaction survey if customer has email
        $customer = $ticket->customer;
        if ($customer && $customer->email && ! $ticket->rated_at) {
            try {
                Mail::to($customer->email)->queue(new TicketSatisfactionSurveyMail($ticket));

                Log::info('Satisfaction survey queued for customer', [
                    'ticket_id' => $ticket->id,
                    'customer_email' => $customer->email,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to queue satisfaction survey', [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Http\Controllers\FeedbackController;
use Modules\HelpdeskTickets\Mail\TicketSatisfactionSurveyMail;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;

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
                // Botones de puntuación 1-5 con enlaces firmados (ruta portal.tickets.rate.email).
                // Antes el blade usaba $ratingUrls, que el Mailable nunca pasaba → email roto.
                $ratingButtons = '';
                for ($i = 1; $i <= 5; $i++) {
                    $rateUrl = URL::signedRoute('portal.tickets.rate.email', [
                        'ticketNumber' => $ticket->ticket_number,
                        'rating' => $i,
                    ]);
                    $ratingButtons .= '<a href="'.e($rateUrl).'" style="display: inline-block; margin: 0 6px; padding: 12px 20px; background: #f9f9f9; border: 2px solid #ddd; border-radius: 50%; font-size: 22px; text-decoration: none; color: #333; font-weight: bold;">'.$i.'</a>';
                }

                [$subject, $content] = TicketMailRenderer::render(
                    'helpdesk_tickets.satisfaction_survey',
                    [
                        'TICKET_NUMBER' => $ticket->ticket_number,
                        'TICKET_SUBJECT' => e($ticket->subject),
                        'CLOSED_AT' => $ticket->closed_at?->format('d/m/Y H:i') ?? '',
                        'RATING_BUTTONS' => $ratingButtons,
                        // Página pública de feedback (rating + comentario). La ruta
                        // exige firma, así que el enlace SIEMPRE debe emitirse aquí.
                        'FEEDBACK_URL' => FeedbackController::signedShowUrl($ticket),
                    ],
                    'Cuéntanos tu experiencia — Ticket #'.$ticket->ticket_number,
                );

                Mail::to($customer->email)->queue(new TicketSatisfactionSurveyMail($ticket, $subject, $content));

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

    public function failed(TicketClosed $event, \Throwable $exception): void
    {
        Log::error('UpdateTicketOnClose listener failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

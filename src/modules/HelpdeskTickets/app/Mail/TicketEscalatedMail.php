<?php

namespace Modules\HelpdeskTickets\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;

class TicketEscalatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public string $oldPriority,
        public string $newPriority,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket escalated: #'.$this->ticket->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'helpdesktickets::emails.ticket-escalated',
            with: [
                'ticketUrl' => route('manager.helpdesk.tickets.show', $this->ticket->id),
            ],
        );
    }
}

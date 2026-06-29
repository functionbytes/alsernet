<?php

namespace Modules\HelpdeskTickets\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;

class TicketAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public User $agent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket assigned to you — #'.$this->ticket->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'helpdesktickets::emails.ticket-assigned',
        );
    }
}

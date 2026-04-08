<?php

namespace Modules\Helpdesk\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Ticket;

class TicketSatisfactionSurveyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cuentanos tu experiencia — Ticket #'.$this->ticket->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'helpdesk::emails.ticket-satisfaction-survey',
        );
    }
}

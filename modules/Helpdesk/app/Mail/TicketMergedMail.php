<?php

namespace Modules\Helpdesk\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Ticket;

class TicketMergedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $sourceTicket,
        public Ticket $targetTicket,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ticket #'.$this->sourceTicket->ticket_number.' has been merged',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'helpdesk::emails.ticket-merged',
            with: [
                'mergedTicketUrl' => route('portal.tickets.show', $this->targetTicket->ticket_number),
            ],
        );
    }
}

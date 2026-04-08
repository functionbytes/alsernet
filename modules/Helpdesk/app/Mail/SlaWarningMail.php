<?php

namespace Modules\Helpdesk\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Ticket;

class SlaWarningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public float $percentUsed,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SLA Warning — Ticket #'.$this->ticket->ticket_number.' ('.round($this->percentUsed).'% used)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'helpdesk::emails.sla-warning',
        );
    }
}

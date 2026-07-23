<?php

namespace Modules\HelpdeskTickets\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\TicketSideConversation;

/**
 * Envía a un contacto externo el mensaje de un side conversation de ticket.
 * La ingesta de la respuesta entrante (por email) queda para una fase posterior.
 */
class TicketSideConversationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly TicketSideConversation $sideConversation,
        public readonly string $bodyText,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->sideConversation->subject);
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>'.nl2br(e($this->bodyText)).'</p>',
        );
    }
}

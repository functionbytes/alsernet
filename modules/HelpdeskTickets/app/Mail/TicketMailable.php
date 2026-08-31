<?php

namespace Modules\HelpdeskTickets\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskEmailLog\Contracts\TracksEmailLog;
use Modules\HelpdeskEmailLog\Mail\AddsEmailLogHeaders;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Base de los correos del módulo que van referidos a un ticket.
 *
 * El patrón es siempre el mismo: el asunto y el cuerpo llegan ya renderizados
 * por TicketMailRenderer desde la plantilla del módulo Mailer, y el Mailable
 * solo transporta el HTML final y los datos que necesita el registro de envíos
 * (TracksEmailLog).
 *
 * Estaba copiado en cinco clases idénticas byte a byte salvo el nombre —
 * TicketAssignedMail y SlaWarningMail no se diferenciaban en nada más. Ahora
 * las subclases no declaran nada: existen para que el destinatario, la
 * plantilla y el listener que las despacha sigan siendo distinguibles por tipo.
 */
abstract class TicketMailable extends Mailable implements ShouldQueue, TracksEmailLog
{
    use AddsEmailLogHeaders, Queueable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $emailSubject,
        public readonly string $emailContent,
    ) {
        $this->onQueue('emails');
    }

    public function getEmailLogModule(): string
    {
        return 'HelpdeskTickets';
    }

    public function getEmailLogEntityType(): string
    {
        return Ticket::class;
    }

    public function getEmailLogEntityId(): int|string
    {
        return $this->ticket->id;
    }

    public function getEmailLogExternalId(): ?string
    {
        return $this->ticket->ticket_number !== null ? (string) $this->ticket->ticket_number : null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->emailContent);
    }
}

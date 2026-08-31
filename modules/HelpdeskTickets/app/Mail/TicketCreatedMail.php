<?php

namespace Modules\HelpdeskTickets\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskEmailLog\Contracts\TracksEmailLog;
use Modules\HelpdeskEmailLog\Mail\AddsEmailLogHeaders;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Asunto y cuerpo ya renderizados por TicketMailRenderer desde la plantilla del
 * módulo Mailer; este Mailable solo transporta el HTML final.
 */
class TicketCreatedMail extends Mailable implements ShouldQueue, TracksEmailLog
{
    // headers() propio abajo necesita añadir Message-ID — mismo motivo que
    // TicketReplyMail: un trait no soporta parent::, así que se alias-ea el
    // método del trait para complementarlo sin perder X-Email-Module/
    // X-Entity-Type/X-Entity-Id en silencio.
    use AddsEmailLogHeaders {
        headers as private emailLogHeaders;
    }
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $emailSubject,
        public readonly string $emailContent,
        // Sin esto, este correo (el PRIMERO del hilo) salía siempre desde el
        // mailer global en vez del buzón real del canal, y no llevaba
        // Message-ID propio para que la respuesta del cliente enganchara por
        // In-Reply-To/References — mismo motivo que TicketReplyMail.
        public readonly ?string $fromAddress = null,
        public readonly ?string $ownMessageId = null,
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
        return new Envelope(
            subject: $this->emailSubject,
            from: $this->fromAddress ? new Address($this->fromAddress) : null,
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->emailContent);
    }

    public function headers(): Headers
    {
        $headers = $this->emailLogHeaders();

        if ($this->ownMessageId) {
            $headers->messageId = trim($this->ownMessageId, '<>');
        }

        return $headers;
    }
}

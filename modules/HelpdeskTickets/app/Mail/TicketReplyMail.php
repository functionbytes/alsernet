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
 * Notifica al cliente sobre su ticket: respuesta de un agente (comentario
 * externo), cambio de estado, o reapertura — mismo transporte para las tres,
 * ya que las tres son "un HTML ya renderizado hacia el cliente del ticket,
 * desde el canal correcto, hilado con el correo anterior". Asunto y cuerpo
 * vienen ya renderizados por TicketMailRenderer/MailerTemplateRendererService
 * desde la plantilla del módulo Mailer; este Mailable solo los transporta.
 */
class TicketReplyMail extends Mailable implements ShouldQueue, TracksEmailLog
{
    // headers() propio abajo necesita añadir Message-ID/In-Reply-To — mismo
    // motivo que TicketComposedMail: un trait no soporta parent::, así que se
    // alias-ea el método del trait para complementarlo sin perder
    // X-Email-Module/X-Entity-Type/X-Entity-Id en silencio.
    use AddsEmailLogHeaders {
        headers as private emailLogHeaders;
    }
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $emailSubject,
        public readonly string $emailContent,
        // Sin esto, Symfony genera su propio remitente/Message-ID: la
        // respuesta sale desde el mailer global en vez del buzón del canal
        // (bug real: SendCustomerReplyNotification usaba config('mail.from.address')
        // para todo, sin relación con qué cuenta recibió el correo original), y
        // el cliente no puede seguir el hilo por Message-ID/In-Reply-To.
        public readonly ?string $fromAddress = null,
        public readonly ?string $ownMessageId = null,
        public readonly ?string $inReplyTo = null,
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

        if ($this->inReplyTo) {
            $id = trim($this->inReplyTo, '<>');
            $headers->references([$id]);
            $headers->text(['In-Reply-To' => "<{$id}>"]);
        }

        return $headers;
    }
}

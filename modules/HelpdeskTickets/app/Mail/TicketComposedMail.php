<?php

namespace Modules\HelpdeskTickets\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskEmailLog\Contracts\TracksEmailLog;
use Modules\HelpdeskEmailLog\Mail\AddsEmailLogHeaders;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Email compuesto manualmente por un agente desde la bandeja "Emails
 * enviados" (redactar/responder/reenviar) — a diferencia de TicketReplyMail,
 * que solo transporta el HTML ya renderizado de la plantilla estándar de
 * respuesta, este soporta CC/BCC y adjuntos sueltos elegidos por el agente.
 *
 * getEmailLogEntityType() devuelve el FQCN (Ticket::class), no el string
 * literal 'ticket' de TracksTicketEmailLog — ese trait tiene un mismatch
 * conocido con la config de HelpdeskEmailLog (entity_labels indexa por FQCN)
 * que rompe el enlace de vuelta al ticket; se implementa la interfaz
 * directamente aquí para no arrastrar el mismo bug, igual que TicketReplyMail.
 */
class TicketComposedMail extends Mailable implements ShouldQueue, TracksEmailLog
{
    // headers() propio abajo necesita añadir el messageId — un trait no
    // soporta parent::, así que se alias-ea el método del trait para poder
    // llamarlo y solo complementarlo (el comentario del propio trait avisa de
    // esto: "If the Mailable already defines headers(), call parent or merge
    // manually"). Sobrescribirlo sin más — como se hizo aquí la primera vez —
    // pierde X-Email-Module/X-Entity-Type/X-Entity-Id en silencio; ver bug
    // real encontrado en QA manual (Trazabilidad no enlazaba el ticket).
    use AddsEmailLogHeaders {
        headers as private emailLogHeaders;
    }
    use Queueable, SerializesModels;

    /**
     * Nombres $ccAddresses/$bccAddresses (no $cc/$bcc): Mailable ya declara
     * $cc/$bcc como propiedades públicas no-readonly — promocionar parámetros
     * readonly con esos nombres choca ("Cannot redeclare non-readonly
     * property... as readonly"). Mismo motivo por el que $emailSubject/
     * $emailContent (no $subject) en TicketReplyMail.
     *
     * @param  array<int, string>  $ccAddresses
     * @param  array<int, string>  $bccAddresses
     * @param  array<int, array{disk: string, path: string, name?: string}>  $attachmentFiles
     */
    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $emailSubject,
        public readonly string $emailContent,
        public readonly array $ccAddresses = [],
        public readonly array $bccAddresses = [],
        public readonly array $attachmentFiles = [],
        // Sin esto, Symfony genera su propio Message-ID y el que ya
        // guardamos en TicketMail.message_id queda huérfano — el tab
        // "Trazabilidad" cruza EmailLog por este valor exacto, así que tienen
        // que coincidir sí o sí.
        public readonly ?string $existingMessageId = null,
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
            cc: array_map(fn (string $email) => new Address($email), $this->ccAddresses),
            bcc: array_map(fn (string $email) => new Address($email), $this->bccAddresses),
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->emailContent);
    }

    public function headers(): Headers
    {
        $headers = $this->emailLogHeaders();
        $headers->messageId = $this->existingMessageId ? trim($this->existingMessageId, '<>') : null;

        return $headers;
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return array_map(
            fn (array $file) => isset($file['name'])
                ? Attachment::fromStorageDisk($file['disk'], $file['path'])->as($file['name'])
                : Attachment::fromStorageDisk($file['disk'], $file['path']),
            $this->attachmentFiles
        );
    }
}

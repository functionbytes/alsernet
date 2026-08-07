<?php

namespace Modules\HelpdeskTickets\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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
    use AddsEmailLogHeaders, Queueable, SerializesModels;

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

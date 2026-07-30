<?php

namespace Modules\HelpdeskTickets\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskEmailLog\Contracts\TracksEmailLog;
use Modules\HelpdeskEmailLog\Mail\AddsEmailLogHeaders;

/**
 * Asunto y cuerpo ya renderizados por TicketMailRenderer desde la plantilla del
 * módulo Mailer; este Mailable solo transporta el HTML final.
 */
class PortalMagicLinkMail extends Mailable implements ShouldQueue, TracksEmailLog
{
    use AddsEmailLogHeaders, Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
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
        return Customer::class;
    }

    public function getEmailLogEntityId(): int|string
    {
        return $this->customer->id;
    }

    public function getEmailLogExternalId(): ?string
    {
        return null;
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

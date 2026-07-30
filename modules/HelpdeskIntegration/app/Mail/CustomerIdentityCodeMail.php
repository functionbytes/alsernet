<?php

namespace Modules\HelpdeskIntegration\Mail;

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
 * El asunto y el cuerpo ya llegan renderizados por
 * CustomerIdentityVerificationService desde la plantilla del modulo Mailer
 * (helpdesk_integration.customer_identity_code) — este Mailable solo
 * transporta el HTML final, igual que AttentionCustomMail/DocumentCustomMail.
 */
class CustomerIdentityCodeMail extends Mailable implements ShouldQueue, TracksEmailLog
{
    use AddsEmailLogHeaders, Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
        public readonly string $emailSubject,
        public readonly string $emailContent,
        public readonly ?int $verificationId = null,
    ) {
        $this->onQueue('emails');
    }

    public function getEmailLogModule(): string
    {
        return 'HelpdeskIntegration';
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
        return $this->verificationId !== null ? (string) $this->verificationId : null;
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

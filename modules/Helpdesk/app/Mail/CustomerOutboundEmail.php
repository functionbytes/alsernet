<?php

namespace Modules\Helpdesk\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskEmailLog\Contracts\TracksEmailLog;
use Modules\HelpdeskEmailLog\Mail\AddsEmailLogHeaders;

class CustomerOutboundEmail extends Mailable implements ShouldQueue, TracksEmailLog
{
    use AddsEmailLogHeaders, Queueable, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $emailSubject,
        public readonly string $emailBodyHtml,
        public readonly ?string $emailBodyPlain = null,
        public readonly array $ccEmails = [],
        public readonly array $bccEmails = [],
        public readonly ?string $externalId = null,
    ) {
        $this->onQueue('emails');
    }

    public function getEmailLogModule(): string
    {
        return 'Helpdesk';
    }

    public function getEmailLogEntityType(): string
    {
        return Conversation::class;
    }

    public function getEmailLogEntityId(): int|string
    {
        return $this->conversation->id;
    }

    public function getEmailLogExternalId(): ?string
    {
        return $this->externalId;
    }

    public function envelope(): Envelope
    {
        $customer = $this->conversation->customer;

        $ccAddresses = array_map(
            fn (string $email) => new Address($email),
            array_filter($this->ccEmails)
        );

        $bccAddresses = array_map(
            fn (string $email) => new Address($email),
            array_filter($this->bccEmails)
        );

        return new Envelope(
            to: [new Address($customer->email, $customer->name ?? '')],
            cc: array_values($ccAddresses),
            bcc: array_values($bccAddresses),
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'helpdesk::emails.customer-outbound',
            with: [
                'conversation' => $this->conversation,
                'bodyHtml' => $this->emailBodyHtml,
                'bodyPlain' => $this->emailBodyPlain,
            ],
        );
    }
}

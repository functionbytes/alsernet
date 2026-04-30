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

class CustomerOutboundEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $subject,
        public readonly string $bodyHtml,
        public readonly ?string $bodyPlain = null,
        public readonly array $cc = [],
        public readonly array $bcc = [],
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        $customer = $this->conversation->customer;

        $ccAddresses = array_map(
            fn (string $email) => new Address($email),
            array_filter($this->cc)
        );

        $bccAddresses = array_map(
            fn (string $email) => new Address($email),
            array_filter($this->bcc)
        );

        return new Envelope(
            to: [new Address($customer->email, $customer->name ?? '')],
            cc: array_values($ccAddresses),
            bcc: array_values($bccAddresses),
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'helpdesk::emails.customer-outbound',
            with: [
                'conversation' => $this->conversation,
                'bodyHtml' => $this->bodyHtml,
                'bodyPlain' => $this->bodyPlain,
            ],
        );
    }
}

<?php

namespace Modules\Remarketing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Remarketing\Models\Customer;

class DoubleOptinMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirma tu suscripción',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'remarketing::emails.double-optin',
            with: [
                'customer' => $this->customer,
                'confirmUrl' => $this->buildConfirmUrl(),
            ],
        );
    }

    private function buildConfirmUrl(): string
    {
        $token = $this->customer->double_optin_token ?? '';

        if ($token === '') {
            return url('/');
        }

        return url('/r/consent/confirm/'.$token);
    }
}

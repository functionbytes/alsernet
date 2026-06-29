<?php

namespace Modules\Remarketing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Remarketing\Models\Customer;

class CartRecoveryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
        public readonly array $cartPayload,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('remarketing::messages.cart_recovery.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'remarketing::emails.cart-recovery',
            with: [
                'customer' => $this->customer,
                'items' => $this->cartPayload['items'] ?? [],
                'total' => $this->cartPayload['total'] ?? 0,
            ],
        );
    }

    public function locale(): string
    {
        return $this->customer->locale ?? config('app.locale', 'es');
    }
}

<?php

namespace Modules\Remarketing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Remarketing\Models\Customer;

class ReplenishmentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
        public readonly int $productId,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('remarketing::messages.replenishment.subject'),
        );
    }

    public function locale(): string
    {
        return $this->customer->locale ?? config('app.locale', 'es');
    }

    public function content(): Content
    {
        return new Content(
            view: 'remarketing::emails.replenishment',
            with: [
                'customer' => $this->customer,
                'productId' => $this->productId,
            ],
        );
    }
}

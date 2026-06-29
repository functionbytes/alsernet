<?php

namespace Modules\Remarketing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Remarketing\Models\Customer;

class BrowseAbandonmentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
        public readonly mixed $productId,
        public readonly array $productData,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '¿Te interesó este producto?');
    }

    public function content(): Content
    {
        return new Content(
            view: 'remarketing::emails.browse-abandonment',
            with: [
                'customer' => $this->customer,
                'productId' => $this->productId,
                'product' => $this->productData,
            ],
        );
    }
}

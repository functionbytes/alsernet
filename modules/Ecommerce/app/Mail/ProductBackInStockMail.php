<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductRestockAlert;

class ProductBackInStockMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly ProductRestockAlert $alert,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "El producto {$this->product->name} ya está disponible",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'ecommerce::emails.product-back-in-stock',
        );
    }
}

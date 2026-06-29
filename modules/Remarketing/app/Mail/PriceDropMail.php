<?php

namespace Modules\Remarketing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Remarketing\Models\Customer;

class PriceDropMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
        public readonly int $productId,
        public readonly float $oldPrice,
        public readonly float $newPrice,
        public readonly array $productPayload,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('remarketing::messages.price_drop.subject'),
        );
    }

    public function locale(): string
    {
        return $this->customer->locale ?? config('app.locale', 'es');
    }

    public function content(): Content
    {
        return new Content(
            view: 'remarketing::emails.price-drop',
            with: [
                'customer' => $this->customer,
                'productId' => $this->productId,
                'oldPrice' => $this->oldPrice,
                'newPrice' => $this->newPrice,
                'dropPercent' => $this->oldPrice > 0
                    ? round((($this->oldPrice - $this->newPrice) / $this->oldPrice) * 100)
                    : 0,
                'productData' => $this->productPayload,
            ],
        );
    }
}

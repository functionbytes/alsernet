<?php

namespace Modules\Remarketing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Remarketing\Models\Customer;

class CrossSellMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
        public readonly array $recommendations,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Productos que te pueden interesar');
    }

    public function content(): Content
    {
        return new Content(
            view: 'remarketing::emails.cross-sell',
            with: [
                'customer' => $this->customer,
                'recommendations' => $this->recommendations,
            ],
        );
    }
}

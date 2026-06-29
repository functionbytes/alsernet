<?php

namespace Modules\Remarketing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Order;

class OrderAnniversaryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
        public readonly Order $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Hace un año compraste esto — ¿lo necesitas de nuevo?');
    }

    public function content(): Content
    {
        return new Content(
            view: 'remarketing::emails.order-anniversary',
            with: [
                'customer' => $this->customer,
                'order' => $this->order,
                'items' => $this->order->items,
            ],
        );
    }
}

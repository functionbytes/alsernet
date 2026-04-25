<?php

namespace Modules\EcommercePayment\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Order;

class OrderConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Orden recibida - '.$this->order->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'ecommerce-payment::emails.order_confirmation',
            text: 'ecommerce-payment::emails.order_confirmation_plain',
            with: [
                'order' => $this->order,
                'items' => $this->order->items,
            ],
        );
    }
}

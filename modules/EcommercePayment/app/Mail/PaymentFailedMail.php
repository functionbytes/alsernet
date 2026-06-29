<?php

namespace Modules\EcommercePayment\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Order;

class PaymentFailedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public string $reason = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pago fallido - Orden '.$this->order->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'ecommerce-payment::emails.payment_failed',
            text: 'ecommerce-payment::emails.payment_failed_plain',
            with: [
                'order' => $this->order,
                'reason' => $this->reason,
            ],
        );
    }
}

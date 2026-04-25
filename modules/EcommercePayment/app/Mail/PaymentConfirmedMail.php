<?php

namespace Modules\EcommercePayment\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\EcommercePayment\Models\Payment;

class PaymentConfirmedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmacion de pago - Orden '.$this->payment->order?->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'ecommerce-payment::emails.payment_confirmed',
            text: 'ecommerce-payment::emails.payment_confirmed_plain',
            with: [
                'payment' => $this->payment,
                'order' => $this->payment->order,
            ],
        );
    }
}

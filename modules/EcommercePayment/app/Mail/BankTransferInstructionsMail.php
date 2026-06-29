<?php

namespace Modules\EcommercePayment\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Order;

class BankTransferInstructionsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly array $bankDetails
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Instrucciones para completar tu pago — Orden #'.$this->order->code);
    }

    public function content(): Content
    {
        return new Content(
            view: 'ecommerce-payment::emails.bank-transfer-instructions',
            text: 'ecommerce-payment::emails.bank-transfer-instructions_plain',
        );
    }
}

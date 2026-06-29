<?php

namespace Modules\Remarketing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Remarketing\Models\Customer;

class WinBackMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Te echamos de menos — descuento exclusivo');
    }

    public function content(): Content
    {
        return new Content(
            view: 'remarketing::emails.win-back',
            with: [
                'customer' => $this->customer,
            ],
        );
    }
}

<?php

namespace Modules\Remarketing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Remarketing\Models\Customer;

class VipRewardMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
        public readonly int $milestone,
        public readonly float $newLifetime,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '¡Gracias por ser un cliente VIP!');
    }

    public function content(): Content
    {
        return new Content(
            view: 'remarketing::emails.vip-reward',
            with: [
                'customer' => $this->customer,
                'milestone' => $this->milestone,
                'newLifetime' => $this->newLifetime,
            ],
        );
    }
}

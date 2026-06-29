<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Order;

class OrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected Order $order,
        protected string $emailSubject,
        protected string $emailContent
    ) {}

    public function build(): static
    {
        return $this->subject($this->emailSubject)
            ->html($this->emailContent);
    }
}

<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Customer;

class AbandonedCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected Customer $customer,
        protected string $emailSubject,
        protected string $emailContent
    ) {}

    public function build(): static
    {
        return $this->subject($this->emailSubject)
            ->html($this->emailContent);
    }
}

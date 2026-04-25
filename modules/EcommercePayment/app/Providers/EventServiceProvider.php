<?php

namespace Modules\EcommercePayment\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\EcommercePayment\Events\PaymentCompleted;
use Modules\EcommercePayment\Listeners\SendPaymentConfirmationEmail;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentCompleted::class => [
            SendPaymentConfirmationEmail::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}

<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Ecommerce\Events\CustomerRegistered;
use Modules\Ecommerce\Notifications\ConfirmEmailNotification;

class SendMailsAfterCustomerRegistered implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public int $backoff = 5;

    public function handle(CustomerRegistered $event): void
    {
        $customer = $event->customer;
        $token = Str::random(60);

        $customer->update(['email_verification_token' => $token]);
        $customer->notify(new ConfirmEmailNotification($token));
    }

    public function failed(CustomerRegistered $event, \Throwable $exception): void
    {
        Log::error('Error enviando email de confirmacion al cliente', [
            'customer_id' => $event->customer->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Ecommerce\Events\CustomerEmailVerified;

class SendMailsAfterCustomerEmailVerified implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public int $backoff = 5;

    public function handle(CustomerEmailVerified $event): void
    {
        $customer = $event->customer;

        Mail::raw(
            "Hola {$customer->name},\n\nTu correo electronico ha sido verificado. Bienvenido a nuestra tienda.\n\nGracias.",
            function ($message) use ($customer) {
                $message->to($customer->email, $customer->name)
                    ->subject('Bienvenido - Correo verificado');
            }
        );

        Log::info('Cliente verifico su correo electronico', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);
    }

    public function failed(CustomerEmailVerified $event, \Throwable $exception): void
    {
        Log::error('Error enviando email de bienvenida al cliente', [
            'customer_id' => $event->customer->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

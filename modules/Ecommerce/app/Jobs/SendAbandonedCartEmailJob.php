<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Services\AbandonedCartEmailService;

class SendAbandonedCartEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 60;

    public function __construct(public int $customerId)
    {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $customer = Customer::find($this->customerId);

        if (! $customer || ! $customer->email) {
            return;
        }

        // Re-fetch cart at execution time — skip if customer already completed purchase
        $cartItems = Cart::query()
            ->where('customer_id', $this->customerId)
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            return;
        }

        AbandonedCartEmailService::send($customer, $cartItems);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendAbandonedCartEmailJob failed', [
            'customer_id' => $this->customerId,
            'error' => $exception->getMessage(),
        ]);
    }
}

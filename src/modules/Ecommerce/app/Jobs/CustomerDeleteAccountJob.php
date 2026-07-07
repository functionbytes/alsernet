<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Models\Customer;

class CustomerDeleteAccountJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    public function __construct(public int $customerId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $customer = Customer::query()->find($this->customerId);
        if ($customer) {
            $customer->delete();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CustomerDeleteAccountJob failed', [
            'customer_id' => $this->customerId,
            'error' => $exception->getMessage(),
        ]);
    }
}

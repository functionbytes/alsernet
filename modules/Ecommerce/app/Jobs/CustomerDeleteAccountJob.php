<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Customer;

class CustomerDeleteAccountJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $customerId) {}

    public function handle(): void
    {
        $customer = Customer::query()->find($this->customerId);
        if ($customer) {
            $customer->delete();
        }
    }
}

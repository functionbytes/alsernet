<?php

namespace Modules\Ecommerce\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Customer;

class CustomerRegistered
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Customer $customer
    ) {}
}

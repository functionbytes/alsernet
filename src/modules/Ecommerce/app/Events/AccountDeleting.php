<?php

namespace Modules\Ecommerce\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Customer;

class AccountDeleting
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Customer $customer) {}
}

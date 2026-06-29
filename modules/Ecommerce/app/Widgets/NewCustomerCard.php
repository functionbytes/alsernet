<?php

namespace Modules\Ecommerce\Widgets;

use Modules\Ecommerce\Models\Customer;

class NewCustomerCard
{
    public function getCount(): int
    {
        return Customer::query()->whereDate('created_at', today())->count();
    }
}

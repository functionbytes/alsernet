<?php

namespace Modules\Ecommerce\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerAddress;

class CustomerAddressPolicy
{
    use HandlesAuthorization;

    public function viewAny(Customer $customer): bool
    {
        return true;
    }

    public function view(Customer $customer, CustomerAddress $address): bool
    {
        return $address->customer_id === $customer->id;
    }

    public function create(Customer $customer): bool
    {
        return true;
    }

    public function update(Customer $customer, CustomerAddress $address): bool
    {
        return $address->customer_id === $customer->id;
    }

    public function delete(Customer $customer, CustomerAddress $address): bool
    {
        return $address->customer_id === $customer->id;
    }
}

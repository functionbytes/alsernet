<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.customer.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('ecommerce.customer.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.customer.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('ecommerce.customer.update');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('ecommerce.customer.delete');
    }
}

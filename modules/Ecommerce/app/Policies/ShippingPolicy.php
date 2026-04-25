<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\Shipping;

class ShippingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.shipping.index');
    }

    public function view(User $user, Shipping $shipping): bool
    {
        return $user->can('ecommerce.shipping.show');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.shipping.store');
    }

    public function update(User $user, Shipping $shipping): bool
    {
        return $user->can('ecommerce.shipping.update');
    }

    public function delete(User $user, Shipping $shipping): bool
    {
        return $user->can('ecommerce.shipping.destroy');
    }
}

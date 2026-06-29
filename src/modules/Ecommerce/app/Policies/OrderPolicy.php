<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.order.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('ecommerce.order.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.order.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('ecommerce.order.update');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('ecommerce.order.delete');
    }
}

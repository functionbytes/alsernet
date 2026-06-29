<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.product.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('ecommerce.product.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.product.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('ecommerce.product.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('ecommerce.product.delete');
    }
}

<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\ProductAttributeSet;

class ProductAttributeSetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.product-attribute-sets.view');
    }

    public function view(User $user, ProductAttributeSet $productAttributeSet): bool
    {
        return $user->hasPermissionTo('ecommerce.product-attribute-sets.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.product-attribute-sets.create');
    }

    public function update(User $user, ProductAttributeSet $productAttributeSet): bool
    {
        return $user->hasPermissionTo('ecommerce.product-attribute-sets.update');
    }

    public function delete(User $user, ProductAttributeSet $productAttributeSet): bool
    {
        return $user->hasPermissionTo('ecommerce.product-attribute-sets.delete');
    }
}

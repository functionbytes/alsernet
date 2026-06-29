<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\ProductCategory;

class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.category.view');
    }

    public function view(User $user, ProductCategory $category): bool
    {
        return $user->hasPermissionTo('ecommerce.category.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.category.create');
    }

    public function update(User $user, ProductCategory $category): bool
    {
        return $user->hasPermissionTo('ecommerce.category.update');
    }

    public function delete(User $user, ProductCategory $category): bool
    {
        return $user->hasPermissionTo('ecommerce.category.delete');
    }
}

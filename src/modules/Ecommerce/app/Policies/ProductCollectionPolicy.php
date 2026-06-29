<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\ProductCollection;

class ProductCollectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.collections.index');
    }

    public function view(User $user, ProductCollection $collection): bool
    {
        return $user->can('ecommerce.collections.show');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.collections.store');
    }

    public function update(User $user, ProductCollection $collection): bool
    {
        return $user->can('ecommerce.collections.update');
    }

    public function delete(User $user, ProductCollection $collection): bool
    {
        return $user->can('ecommerce.collections.destroy');
    }
}

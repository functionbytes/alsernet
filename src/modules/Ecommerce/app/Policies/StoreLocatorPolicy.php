<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\StoreLocator;

class StoreLocatorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.store-locators.index');
    }

    public function view(User $user, StoreLocator $storeLocator): bool
    {
        return $user->can('ecommerce.store-locators.show');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.store-locators.store');
    }

    public function update(User $user, StoreLocator $storeLocator): bool
    {
        return $user->can('ecommerce.store-locators.update');
    }

    public function delete(User $user, StoreLocator $storeLocator): bool
    {
        return $user->can('ecommerce.store-locators.destroy');
    }
}

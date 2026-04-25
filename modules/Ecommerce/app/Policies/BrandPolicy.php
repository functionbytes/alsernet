<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\Brand;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.brand.view');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->hasPermissionTo('ecommerce.brand.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.brand.create');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->hasPermissionTo('ecommerce.brand.update');
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->hasPermissionTo('ecommerce.brand.delete');
    }
}

<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\SpecificationGroup;

class SpecificationGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-groups.view');
    }

    public function view(User $user, SpecificationGroup $specificationGroup): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-groups.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-groups.create');
    }

    public function update(User $user, SpecificationGroup $specificationGroup): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-groups.update');
    }

    public function delete(User $user, SpecificationGroup $specificationGroup): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-groups.delete');
    }
}

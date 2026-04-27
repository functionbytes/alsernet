<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\SpecificationTable;

class SpecificationTablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-tables.view');
    }

    public function view(User $user, SpecificationTable $specificationTable): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-tables.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-tables.create');
    }

    public function update(User $user, SpecificationTable $specificationTable): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-tables.update');
    }

    public function delete(User $user, SpecificationTable $specificationTable): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-tables.delete');
    }
}

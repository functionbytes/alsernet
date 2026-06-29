<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\SpecificationAttribute;

class SpecificationAttributePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-attributes.view');
    }

    public function view(User $user, SpecificationAttribute $specificationAttribute): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-attributes.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-attributes.create');
    }

    public function update(User $user, SpecificationAttribute $specificationAttribute): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-attributes.update');
    }

    public function delete(User $user, SpecificationAttribute $specificationAttribute): bool
    {
        return $user->hasPermissionTo('ecommerce.specification-attributes.delete');
    }
}

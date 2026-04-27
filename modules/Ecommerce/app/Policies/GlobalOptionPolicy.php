<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\GlobalOption;

class GlobalOptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.global-options.view');
    }

    public function view(User $user, GlobalOption $globalOption): bool
    {
        return $user->hasPermissionTo('ecommerce.global-options.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.global-options.create');
    }

    public function update(User $user, GlobalOption $globalOption): bool
    {
        return $user->hasPermissionTo('ecommerce.global-options.update');
    }

    public function delete(User $user, GlobalOption $globalOption): bool
    {
        return $user->hasPermissionTo('ecommerce.global-options.delete');
    }
}

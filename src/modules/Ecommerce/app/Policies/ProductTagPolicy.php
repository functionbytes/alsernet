<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\ProductTag;

class ProductTagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.tags.index');
    }

    public function view(User $user, ProductTag $tag): bool
    {
        return $user->can('ecommerce.tags.show');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.tags.store');
    }

    public function update(User $user, ProductTag $tag): bool
    {
        return $user->can('ecommerce.tags.update');
    }

    public function delete(User $user, ProductTag $tag): bool
    {
        return $user->can('ecommerce.tags.destroy');
    }
}

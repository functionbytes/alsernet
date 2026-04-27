<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Ecommerce\Models\ProductLabel;

class ProductLabelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.product-labels.view');
    }

    public function view(User $user, ProductLabel $productLabel): bool
    {
        return $user->can('ecommerce.product-labels.view');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.product-labels.create');
    }

    public function update(User $user, ProductLabel $productLabel): bool
    {
        return $user->can('ecommerce.product-labels.update');
    }

    public function delete(User $user, ProductLabel $productLabel): bool
    {
        return $user->can('ecommerce.product-labels.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('ecommerce.product-labels.manage');
    }
}

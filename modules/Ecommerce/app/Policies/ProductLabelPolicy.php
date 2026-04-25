<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\ProductLabel;

class ProductLabelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.labels.index');
    }

    public function view(User $user, ProductLabel $label): bool
    {
        return $user->can('ecommerce.labels.show');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.labels.store');
    }

    public function update(User $user, ProductLabel $label): bool
    {
        return $user->can('ecommerce.labels.update');
    }

    public function delete(User $user, ProductLabel $label): bool
    {
        return $user->can('ecommerce.labels.destroy');
    }
}

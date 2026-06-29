<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\Tax;

class TaxPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.taxes.index');
    }

    public function view(User $user, Tax $tax): bool
    {
        return $user->can('ecommerce.taxes.show');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.taxes.store');
    }

    public function update(User $user, Tax $tax): bool
    {
        return $user->can('ecommerce.taxes.update');
    }

    public function delete(User $user, Tax $tax): bool
    {
        return $user->can('ecommerce.taxes.destroy');
    }
}

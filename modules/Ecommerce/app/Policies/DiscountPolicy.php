<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\Discount;

class DiscountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.discounts.index');
    }

    public function view(User $user, Discount $discount): bool
    {
        return $user->can('ecommerce.discounts.show');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.discounts.store');
    }

    public function update(User $user, Discount $discount): bool
    {
        return $user->can('ecommerce.discounts.update');
    }

    public function delete(User $user, Discount $discount): bool
    {
        return $user->can('ecommerce.discounts.destroy');
    }
}

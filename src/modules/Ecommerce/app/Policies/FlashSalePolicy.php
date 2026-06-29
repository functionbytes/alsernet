<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\FlashSale;

class FlashSalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.flash-sales.index');
    }

    public function view(User $user, FlashSale $flashSale): bool
    {
        return $user->can('ecommerce.flash-sales.show');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.flash-sales.store');
    }

    public function update(User $user, FlashSale $flashSale): bool
    {
        return $user->can('ecommerce.flash-sales.update');
    }

    public function delete(User $user, FlashSale $flashSale): bool
    {
        return $user->can('ecommerce.flash-sales.destroy');
    }
}

<?php

namespace Modules\PriceLabels\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\PriceLabels\Models\PriceLabelGeneration;

class PriceLabelGenerationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('pricelabels.view');
    }

    public function view(User $user, PriceLabelGeneration $generation): bool
    {
        return $user->can('pricelabels.view');
    }

    public function delete(User $user, ?PriceLabelGeneration $generation = null): bool
    {
        return $user->can('pricelabels.delete');
    }
}

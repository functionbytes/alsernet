<?php

namespace Modules\PriceLabels\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\PriceLabels\Models\PriceLabelTemplate;

class PriceLabelTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('pricelabels.view');
    }

    public function view(User $user, PriceLabelTemplate $priceLabelTemplate): bool
    {
        return $user->can('pricelabels.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pricelabels.create');
    }

    public function update(User $user, PriceLabelTemplate $priceLabelTemplate): bool
    {
        return $user->can('pricelabels.update');
    }

    public function delete(User $user, ?PriceLabelTemplate $priceLabelTemplate = null): bool
    {
        return $user->can('pricelabels.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('pricelabels.manage');
    }
}

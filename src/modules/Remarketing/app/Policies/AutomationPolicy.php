<?php

namespace Modules\Remarketing\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Remarketing\Models\Automation;

class AutomationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('remarketing.automations.view');
    }

    public function view(User $user, Automation $automation): bool
    {
        return $user->can('remarketing.automations.view') && $this->owns($user, $automation);
    }

    public function create(User $user): bool
    {
        return $user->can('remarketing.automations.create');
    }

    public function update(User $user, Automation $automation): bool
    {
        return $user->can('remarketing.automations.update') && $this->owns($user, $automation);
    }

    public function delete(User $user, Automation $automation): bool
    {
        return $user->can('remarketing.automations.delete') && $this->owns($user, $automation);
    }

    public function manage(User $user): bool
    {
        return $user->can('remarketing.manage');
    }

    protected function owns(User $user, Automation $automation): bool
    {
        if ($user->can('remarketing.manage')) {
            return true;
        }

        return $automation->store?->user_id === $user->id;
    }
}

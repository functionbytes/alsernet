<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskTickets\Models\Automation;

class AutomationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function view(User $user, Automation $automation): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function create(User $user): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function update(User $user, Automation $automation): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function delete(User $user, Automation $automation): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdesk.tickets.manage');
    }
}

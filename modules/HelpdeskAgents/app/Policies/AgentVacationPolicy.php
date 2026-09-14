<?php

namespace Modules\HelpdeskAgents\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskAgents\Models\AgentVacation;

class AgentVacationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdesk.schedule.view');
    }

    public function view(User $user, AgentVacation $vacation): bool
    {
        return $user->can('helpdesk.schedule.view')
            || $vacation->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('helpdesk.schedule.update');
    }

    public function update(User $user, AgentVacation $vacation): bool
    {
        return $user->can('helpdesk.schedule.update');
    }

    public function delete(User $user, AgentVacation $vacation): bool
    {
        return $user->can('helpdesk.schedule.update');
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdesk.schedule.update');
    }
}

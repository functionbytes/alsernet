<?php

namespace Modules\HelpdeskAgents\Policies;

use App\Models\User;
use Modules\HelpdeskAgents\Models\AgentShift;

class AgentShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.schedule.view');
    }

    public function view(User $user, AgentShift $agentShift): bool
    {
        return $user->hasPermissionTo('helpdesk.schedule.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.schedule.create');
    }

    public function update(User $user, AgentShift $agentShift): bool
    {
        return $user->hasPermissionTo('helpdesk.schedule.update');
    }

    public function delete(User $user, AgentShift $agentShift): bool
    {
        return $user->hasPermissionTo('helpdesk.schedule.delete');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.schedule.manage');
    }
}

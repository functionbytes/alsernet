<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\AgentShift;

class AgentShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.shifts.view');
    }

    public function view(User $user, AgentShift $agentShift): bool
    {
        return $user->hasPermissionTo('helpdesk.shifts.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.shifts.create');
    }

    public function update(User $user, AgentShift $agentShift): bool
    {
        return $user->hasPermissionTo('helpdesk.shifts.update');
    }

    public function delete(User $user, AgentShift $agentShift): bool
    {
        return $user->hasPermissionTo('helpdesk.shifts.delete');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.shifts.manage');
    }
}

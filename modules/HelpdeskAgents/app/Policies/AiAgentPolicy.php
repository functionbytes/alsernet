<?php

namespace Modules\HelpdeskAgents\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskAgents\Models\AiAgent;

class AiAgentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdesk.aiagents.view');
    }

    public function view(User $user, AiAgent $agent): bool
    {
        return $user->can('helpdesk.aiagents.view');
    }

    public function create(User $user): bool
    {
        return $user->can('helpdesk.aiagents.create');
    }

    public function update(User $user, AiAgent $agent): bool
    {
        return $user->can('helpdesk.aiagents.update');
    }

    public function delete(User $user, AiAgent $agent): bool
    {
        return $user->can('helpdesk.aiagents.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdesk.aiagents.manage');
    }
}

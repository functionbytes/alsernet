<?php

namespace Modules\HelpdeskCampaigns\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskCampaigns\Models\Campaign;

class CampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdesk.campaigns.view');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->can('helpdesk.campaigns.view');
    }

    public function create(User $user): bool
    {
        return $user->can('helpdesk.campaigns.create');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->can('helpdesk.campaigns.update');
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->can('helpdesk.campaigns.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdesk.campaigns.manage');
    }
}

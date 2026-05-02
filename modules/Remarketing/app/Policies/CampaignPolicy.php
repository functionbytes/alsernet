<?php

namespace Modules\Remarketing\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Remarketing\Models\Campaign;

class CampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('remarketing.campaigns.view');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->can('remarketing.campaigns.view') && $this->owns($user, $campaign);
    }

    public function create(User $user): bool
    {
        return $user->can('remarketing.campaigns.create');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->can('remarketing.campaigns.update') && $this->owns($user, $campaign);
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->can('remarketing.campaigns.delete') && $this->owns($user, $campaign);
    }

    public function send(User $user, Campaign $campaign): bool
    {
        return $user->can('remarketing.campaigns.send') && $this->owns($user, $campaign);
    }

    public function manage(User $user): bool
    {
        return $user->can('remarketing.manage');
    }

    protected function owns(User $user, Campaign $campaign): bool
    {
        if ($user->can('remarketing.manage')) {
            return true;
        }

        return $campaign->store?->user_id === $user->id;
    }
}

<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Campaigns\Campaign;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function restore(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function forceDelete(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function publish(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function pause(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function resume(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function end(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function duplicate(User $user, Campaign $campaign): bool
    {
        return true;
    }
}

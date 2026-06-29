<?php

namespace Modules\Social\Policies;

use App\Models\User;
use Modules\Social\Models\Campaign;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('social.view-campaigns');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->account_id === $campaign->account_id && $user->can('social.view-campaigns');
    }

    public function create(User $user): bool
    {
        return $user->can('social.create-campaigns');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->account_id === $campaign->account_id && $user->can('social.edit-campaigns');
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->account_id === $campaign->account_id && $user->can('social.delete-campaigns');
    }
}

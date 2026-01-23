<?php

namespace Modules\Campaign\Policies;

use App\Models\User;
use Modules\Campaign\Entities\Campaign;

class CampaignPolicy
{
    /**
     * Determine whether the user can view any campaigns.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated manager users can view campaigns
        return true;
    }

    /**
     * Determine whether the user can view the campaign.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        // All authenticated manager users can view a specific campaign
        return true;
    }

    /**
     * Determine whether the user can create campaigns.
     */
    public function create(User $user): bool
    {
        // All authenticated manager users can create campaigns
        return true;
    }

    /**
     * Determine whether the user can update the campaign.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        // All authenticated manager users can update campaigns
        return true;
    }

    /**
     * Determine whether the user can delete the campaign.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        // Only allow deletion of draft or paused campaigns
        return in_array($campaign->status, ['draft', 'paused']);
    }

    /**
     * Determine whether the user can restore the campaign.
     */
    public function restore(User $user, Campaign $campaign): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the campaign.
     */
    public function forceDelete(User $user, Campaign $campaign): bool
    {
        // Require explicit permission for permanent deletion
        return $user->hasPermissionTo('campaigns.force-delete');
    }
}

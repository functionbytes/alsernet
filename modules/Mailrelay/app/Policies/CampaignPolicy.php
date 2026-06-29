<?php

namespace Modules\Mailrelay\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Enums\CampaignStatus;

class CampaignPolicy
{
    use HandlesAuthorization, HasSafePermissionCheck;

    /**
     * Determine if the user can view any campaigns.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'mailrelay.campaigns.view');
    }

    /**
     * Determine if the user can view the campaign.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        return $this->hasPermission($user, 'mailrelay.campaigns.view');
    }

    /**
     * Determine if the user can create campaigns.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'mailrelay.campaigns.create');
    }

    /**
     * Determine if the user can update the campaign.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        // Cannot edit sent campaigns
        if ($campaign->isSent()) {
            return false;
        }

        return $this->hasPermission($user, 'mailrelay.campaigns.edit');
    }

    /**
     * Determine if the user can delete the campaign.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        // Cannot delete sent or sending campaigns
        if ($campaign->isSent() || $campaign->status === CampaignStatus::SENDING) {
            return false;
        }

        return $this->hasPermission($user, 'mailrelay.campaigns.delete');
    }

    /**
     * Determine if the user can send the campaign.
     */
    public function send(User $user, Campaign $campaign): bool
    {
        return $this->hasPermission($user, 'mailrelay.campaigns.send');
    }

    /**
     * Determine if the user can view campaign analytics.
     */
    public function viewAnalytics(User $user, Campaign $campaign): bool
    {
        return $this->hasPermission($user, 'mailrelay.campaigns.view');
    }

    /**
     * Determine if the user can send a test email for the campaign.
     */
    public function sendTest(User $user, Campaign $campaign): bool
    {
        return $this->hasPermission($user, 'mailrelay.campaigns.send');
    }

    /**
     * Determine if the user can schedule the campaign.
     */
    public function schedule(User $user, Campaign $campaign): bool
    {
        // Solo se pueden programar campaigns en estado draft
        if ($campaign->status->value !== 'draft') {
            return false;
        }

        return $this->hasPermission($user, 'mailrelay.campaigns.send');
    }

    /**
     * Determine if the user can duplicate the campaign.
     */
    public function duplicate(User $user, Campaign $campaign): bool
    {
        return $this->hasPermission($user, 'mailrelay.campaigns.create');
    }

    /**
     * Determine if the user can preview the campaign.
     */
    public function preview(User $user, Campaign $campaign): bool
    {
        return $this->hasPermission($user, 'mailrelay.campaigns.view');
    }

    /**
     * Determine if the user can restore the campaign.
     */
    public function restore(User $user, Campaign $campaign): bool
    {
        return $this->hasPermission($user, 'mailrelay.campaigns.delete');
    }

    /**
     * Determine if the user can permanently delete the campaign.
     */
    public function forceDelete(User $user, Campaign $campaign): bool
    {
        return $this->hasPermission($user, 'mailrelay.campaigns.delete');
    }
}

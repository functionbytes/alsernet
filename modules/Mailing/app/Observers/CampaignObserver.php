<?php

namespace Modules\Mailing\Observers;

use Illuminate\Support\Str;
use Modules\Mailing\Models\Campaign;

/**
 * CampaignObserver
 *
 * Handles automatic tracking and lifecycle management for Campaign models.
 * Migrated from Acelle Mail to Alsernet Mailing module.
 */
class CampaignObserver
{
    /**
     * Handle the Campaign "creating" event.
     */
    public function creating(Campaign $campaign): void
    {
        // Generate unique identifier if not set
        if (empty($campaign->uid)) {
            $campaign->uid = $this->generateUid();
        }

        // Set default status if not set
        if (empty($campaign->status)) {
            $campaign->status = 'draft';
        }

        // Set default metadata if not set
        if (empty($campaign->metadata)) {
            $campaign->metadata = [];
        }

        // Set default recipient count
        if (is_null($campaign->recipient_count)) {
            $campaign->recipient_count = 0;
        }
    }

    /**
     * Handle the Campaign "created" event.
     */
    public function created(Campaign $campaign): void
    {
        // Initialize campaign analytics
        if (! $campaign->analytics()->exists()) {
            $campaign->analytics()->create([
                'delivered_count' => 0,
                'failed_count' => 0,
                'opened_count' => 0,
                'clicked_count' => 0,
                'bounced_count' => 0,
                'unsubscribed_count' => 0,
                'complained_count' => 0,
            ]);
        }

        // Log campaign creation
        activity()
            ->performedOn($campaign)
            ->withProperties([
                'campaign_name' => $campaign->name,
                'status' => $campaign->status,
            ])
            ->log('Campaign created');
    }

    /**
     * Handle the Campaign "updating" event.
     */
    public function updating(Campaign $campaign): void
    {
        // Track status changes
        if ($campaign->isDirty('status')) {
            $oldStatus = $campaign->getOriginal('status');
            $newStatus = $campaign->status;

            // Update sent_at when status changes to sent
            if ($newStatus === 'sent' && empty($campaign->sent_at)) {
                $campaign->sent_at = now();
            }

            // Log status change
            activity()
                ->performedOn($campaign)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ])
                ->log('Campaign status changed');
        }
    }

    /**
     * Handle the Campaign "updated" event.
     */
    public function updated(Campaign $campaign): void
    {
        // Clear campaign cache
        $this->clearCampaignCache($campaign);

        // Log significant updates
        if ($campaign->wasChanged(['name', 'subject', 'html_content'])) {
            activity()
                ->performedOn($campaign)
                ->withProperties([
                    'changed_fields' => array_keys($campaign->getChanges()),
                ])
                ->log('Campaign updated');
        }
    }

    /**
     * Handle the Campaign "deleting" event.
     */
    public function deleting(Campaign $campaign): void
    {
        // Log campaign deletion
        activity()
            ->performedOn($campaign)
            ->withProperties([
                'campaign_name' => $campaign->name,
                'status' => $campaign->status,
                'recipient_count' => $campaign->recipient_count,
            ])
            ->log('Campaign deleted');
    }

    /**
     * Handle the Campaign "deleted" event.
     */
    public function deleted(Campaign $campaign): void
    {
        // Clear all campaign-related cache
        $this->clearCampaignCache($campaign);

        // Delete related analytics (if not using cascade)
        if ($campaign->analytics()->exists()) {
            $campaign->analytics()->delete();
        }
    }

    /**
     * Handle the Campaign "forceDeleted" event.
     */
    public function forceDeleted(Campaign $campaign): void
    {
        // Same as deleted for force delete
        $this->clearCampaignCache($campaign);
    }

    /**
     * Generate a unique identifier for the campaign
     */
    protected function generateUid(): string
    {
        do {
            $uid = uniqid(Str::random(10));
        } while (Campaign::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Clear campaign-related cache
     */
    protected function clearCampaignCache(Campaign $campaign): void
    {
        // Clear cache for this specific campaign
        cache()->forget("campaign.{$campaign->id}");
        cache()->forget("campaign.uid.{$campaign->uid}");

        // Clear campaign list cache
        cache()->forget('campaigns.list');
        cache()->forget('campaigns.count');

        // Clear user-specific campaign cache if available
        if ($campaign->user_id) {
            cache()->forget("user.{$campaign->user_id}.campaigns");
        }
    }
}

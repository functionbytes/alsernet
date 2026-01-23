<?php

namespace Modules\Campaign\Services;

use Modules\Campaign\Entities\Campaign;

/**
 * Service class for campaign-related business logic.
 *
 * Encapsulates campaign operations and complex business logic
 * to keep controllers clean and promote code reusability.
 */
class CampaignService
{
    /**
     * Create a new campaign instance.
     *
     * @param  array  $data  Campaign data
     */
    public function create(array $data): Campaign
    {
        $campaign = new Campaign;
        $campaign->fill($data);
        $campaign->save();

        return $campaign;
    }

    /**
     * Update an existing campaign.
     *
     * @param  Campaign  $campaign  The campaign to update
     * @param  array  $data  Updated campaign data
     */
    public function update(Campaign $campaign, array $data): Campaign
    {
        $campaign->update($data);

        return $campaign;
    }

    /**
     * Delete a campaign.
     *
     * @param  Campaign  $campaign  The campaign to delete
     */
    public function delete(Campaign $campaign): bool
    {
        return $campaign->delete();
    }

    /**
     * Execute a campaign.
     *
     * @param  Campaign  $campaign  The campaign to execute
     */
    public function execute(Campaign $campaign): bool
    {
        try {
            $campaign->execute();

            return true;
        } catch (\Exception $exception) {
            \Log::error('Campaign execution failed: '.$exception->getMessage(), [
                'campaign_id' => $campaign->id,
                'campaign_uid' => $campaign->uid,
                'exception' => $exception,
            ]);

            return false;
        }
    }

    /**
     * Pause a campaign.
     *
     * @param  Campaign  $campaign  The campaign to pause
     */
    public function pause(Campaign $campaign): bool
    {
        try {
            $campaign->pause();

            return true;
        } catch (\Exception $exception) {
            \Log::error('Campaign pause failed: '.$exception->getMessage(), [
                'campaign_id' => $campaign->id,
                'campaign_uid' => $campaign->uid,
                'exception' => $exception,
            ]);

            return false;
        }
    }

    /**
     * Resume a paused campaign.
     *
     * @param  Campaign  $campaign  The campaign to resume
     */
    public function resume(Campaign $campaign): bool
    {
        try {
            $campaign->resume();

            return true;
        } catch (\Exception $exception) {
            \Log::error('Campaign resume failed: '.$exception->getMessage(), [
                'campaign_id' => $campaign->id,
                'campaign_uid' => $campaign->uid,
                'exception' => $exception,
            ]);

            return false;
        }
    }

    /**
     * Get campaign statistics.
     *
     * @param  Campaign  $campaign  The campaign to get statistics for
     * @return array Campaign statistics
     */
    public function getStatistics(Campaign $campaign): array
    {
        return [
            'subscriber_count' => $campaign->subscribersCount(),
            'delivered_count' => $campaign->deliveredCount(),
            'delivered_rate' => $campaign->deliveredRate(),
            'unique_open_count' => $campaign->uniqueOpenCount(),
            'open_rate' => $campaign->openRate(),
            'click_count' => $campaign->clickCount(),
            'click_rate' => $campaign->clickRate(),
            'bounce_count' => $campaign->bounceCount(),
            'unsubscribe_count' => $campaign->unsubscribeCount(),
            'abuse_feedback_count' => $campaign->abuseFeedbackCount(),
        ];
    }
}

<?php

namespace Modules\HelpdeskCampaigns\Observers;

use Modules\HelpdeskCampaigns\Models\Campaign;

/**
 * Records campaign lifecycle changes to the spatie/activitylog channel.
 * Wraps in try/catch to keep the module functional even when the package
 * is not installed.
 */
class CampaignObserver
{
    public function created(Campaign $campaign): void
    {
        $this->log('created', $campaign, [
            'name' => $campaign->name,
            'type' => $campaign->type ?? null,
            'status' => $campaign->status,
        ]);
    }

    public function updated(Campaign $campaign): void
    {
        $changes = $campaign->getChanges();

        if (empty($changes) || array_keys($changes) === ['updated_at']) {
            return;
        }

        $this->log('updated', $campaign, [
            'changes' => $changes,
            'original' => array_intersect_key($campaign->getOriginal(), $changes),
        ]);
    }

    public function deleted(Campaign $campaign): void
    {
        $this->log('deleted', $campaign, [
            'name' => $campaign->name,
            'soft' => method_exists($campaign, 'trashed'),
        ]);
    }

    public function restored(Campaign $campaign): void
    {
        $this->log('restored', $campaign, ['name' => $campaign->name]);
    }

    private function log(string $event, Campaign $campaign, array $properties): void
    {
        if (! function_exists('activity')) {
            return;
        }

        try {
            activity('helpdesk-campaigns')
                ->performedOn($campaign)
                ->causedBy(auth()->user())
                ->withProperties($properties)
                ->log("campaign.{$event}");
        } catch (\Throwable $e) {
            // activitylog package or table not present — silently no-op
        }
    }
}

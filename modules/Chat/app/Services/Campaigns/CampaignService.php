<?php

namespace Modules\Chat\Services\Campaigns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Chat\Models\Campaigns\Campaign;
use Modules\Chat\Models\Campaigns\CampaignTemplate;

class CampaignService
{
    /**
     * Return a filtered, sorted, paginated list of campaigns.
     */
    public function paginatedCampaigns(Request $request): LengthAwarePaginator
    {
        return Campaign::query()
            ->when($request->filled('status'), fn ($q) => $q->byStatus($request->status))
            ->when($request->filled('type'), fn ($q) => $q->byType($request->type))
            ->when($request->filled('search'), fn ($q) => $q->search($request->search))
            ->orderBy($request->query('sort_by', 'created_at'), $request->query('sort_order', 'desc'))
            ->paginate(20);
    }

    /**
     * Return a filtered, paginated list of campaign templates.
     */
    public function paginatedTemplates(Request $request): LengthAwarePaginator
    {
        return CampaignTemplate::query()
            ->when($request->filled('category'), fn ($q) => $q->byCategory($request->category))
            ->when($request->filled('search'), fn ($q) => $q->search($request->search))
            ->orderBy('name')
            ->paginate(20);
    }

    /**
     * Create a campaign from validated request data.
     */
    public function create(Request $request): Campaign
    {
        return Campaign::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'status' => $request->status ?? 'draft',
            'content' => $request->content,
            'appearance' => $request->appearance,
            'conditions' => $request->conditions,
            'published_at' => $request->published_at,
            'ends_at' => $request->ends_at,
        ]);
    }

    /**
     * Update a campaign from validated request data.
     */
    public function update(Campaign $campaign, Request $request): Campaign
    {
        $campaign->update([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'status' => $request->status ?? $campaign->status,
            'content' => $request->content,
            'appearance' => $request->appearance,
            'conditions' => $request->conditions,
            'published_at' => $request->published_at,
            'ends_at' => $request->ends_at,
        ]);

        return $campaign->fresh();
    }

    /**
     * Publish a campaign.
     *
     * @throws \InvalidArgumentException when the campaign has no content.
     */
    public function publish(Campaign $campaign): Campaign
    {
        if (empty($campaign->content)) {
            throw new \InvalidArgumentException('Cannot publish campaign without content');
        }

        $campaign->publish();

        return $campaign->fresh();
    }

    /**
     * Pause an active campaign.
     *
     * @throws \InvalidArgumentException when the campaign is not active.
     */
    public function pause(Campaign $campaign): Campaign
    {
        if ($campaign->status !== 'active') {
            throw new \InvalidArgumentException('Only active campaigns can be paused');
        }

        $campaign->pause();

        return $campaign->fresh();
    }

    /**
     * Resume a paused campaign.
     *
     * @throws \InvalidArgumentException when the campaign is not paused.
     */
    public function resume(Campaign $campaign): Campaign
    {
        if ($campaign->status !== 'paused') {
            throw new \InvalidArgumentException('Only paused campaigns can be resumed');
        }

        $campaign->resume();

        return $campaign->fresh();
    }

    /**
     * End an active or paused campaign.
     *
     * @throws \InvalidArgumentException when the campaign is not active or paused.
     */
    public function end(Campaign $campaign): Campaign
    {
        if (! in_array($campaign->status, ['active', 'paused'])) {
            throw new \InvalidArgumentException('Only active or paused campaigns can be ended');
        }

        $campaign->end();

        return $campaign->fresh();
    }

    /**
     * Duplicate a campaign as a draft.
     */
    public function duplicate(Campaign $original): Campaign
    {
        return Campaign::create([
            'name' => $original->name.' (Copy)',
            'description' => $original->description,
            'type' => $original->type,
            'status' => 'draft',
            'content' => $original->content,
            'appearance' => $original->appearance,
            'conditions' => $original->conditions,
            'metadata' => $original->metadata,
        ]);
    }

    /**
     * Build statistics summary for a campaign.
     *
     * @return array<string, mixed>
     */
    public function statistics(Campaign $campaign): array
    {
        $totalImpressions = $campaign->impressions()->count();
        $totalClicks = $campaign->impressions()->clicked()->count();
        $ctr = $totalImpressions > 0
            ? round(($totalClicks / $totalImpressions) * 100, 2)
            : 0;

        return [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'type' => $campaign->type,
                'status' => $campaign->status,
            ],
            'overview' => [
                'total_impressions' => $totalImpressions,
                'total_clicks' => $totalClicks,
                'ctr' => $ctr,
                'average_daily_impressions' => $campaign->average_daily_impressions,
                'average_time_to_click' => $campaign->getAverageTimeToClick(),
            ],
            'devices' => $campaign->getDeviceStatistics(),
            'countries' => $campaign->getCountryStatistics(),
            'trends' => [
                'daily_impressions' => $campaign->getDailyImpressionsTrend(),
                'daily_clicks' => $campaign->getDailyClicksTrend(),
            ],
        ];
    }
}

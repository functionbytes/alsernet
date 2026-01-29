<?php

namespace Modules\Mailing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mailing\Models\Campaign;
use Modules\Mailing\Models\CampaignLink;
use Modules\Mailing\Models\Subscriber;
use Tests\TestCase;

class CampaignTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_campaign_link(): void
    {
        $campaign = Campaign::factory()->create();

        $link = CampaignLink::create([
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/product',
            'click_count' => 0,
        ]);

        $this->assertDatabaseHas('mailing_campaign_links', [
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/product',
        ]);

        $this->assertEquals($campaign->id, $link->campaign_id);
    }

    public function test_campaign_link_can_increment_click_count(): void
    {
        $campaign = Campaign::factory()->create();

        $link = CampaignLink::create([
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/product',
            'click_count' => 0,
        ]);

        $link->increment('click_count');
        $link->increment('click_count');
        $link->increment('click_count');

        $this->assertEquals(3, $link->fresh()->click_count);
    }

    public function test_campaign_has_many_links(): void
    {
        $campaign = Campaign::factory()->create();

        CampaignLink::create([
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/product1',
            'click_count' => 5,
        ]);

        CampaignLink::create([
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/product2',
            'click_count' => 10,
        ]);

        $campaign->refresh();

        $this->assertCount(2, $campaign->links);
        $this->assertEquals(15, $campaign->links->sum('click_count'));
    }

    public function test_can_track_unique_clicks_per_subscriber(): void
    {
        $campaign = Campaign::factory()->create();
        $subscriber = Subscriber::factory()->create();

        $link = CampaignLink::create([
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/product',
            'click_count' => 0,
        ]);

        $link->increment('click_count');
        $link->increment('click_count');

        $this->assertEquals(2, $link->fresh()->click_count);
    }

    public function test_most_clicked_links_for_campaign(): void
    {
        $campaign = Campaign::factory()->create();

        $link1 = CampaignLink::create([
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/product1',
            'click_count' => 100,
        ]);

        $link2 = CampaignLink::create([
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/product2',
            'click_count' => 50,
        ]);

        $link3 = CampaignLink::create([
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/product3',
            'click_count' => 200,
        ]);

        $mostClicked = CampaignLink::where('campaign_id', $campaign->id)
            ->orderBy('click_count', 'desc')
            ->first();

        $this->assertEquals($link3->id, $mostClicked->id);
        $this->assertEquals(200, $mostClicked->click_count);
    }
}

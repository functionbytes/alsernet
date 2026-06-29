<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Http\Controllers\Api\CampaignStatsController;
use Modules\Campaign\Models\Campaign;
use Tests\TestCase;

class CampaignStatsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_endpoint_returns_materialized_metrics(): void
    {
        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
            'sent_count' => 100,
            'open_count' => 25,
            'click_count' => 10,
            'bounce_count' => 5,
        ]);

        $controller = new CampaignStatsController;
        $response = $controller->stats($campaign->uid);

        $data = $response->getData(true);
        $this->assertSame(100, $data['sent_count']);
        $this->assertEqualsWithDelta(25.0, $data['open_rate'], 0.01);
        $this->assertEqualsWithDelta(10.0, $data['click_rate'], 0.01);
    }
}

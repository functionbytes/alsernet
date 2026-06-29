<?php

namespace Modules\Campaign\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignClickLog;
use Modules\Campaign\Models\CampaignOpenLog;
use Modules\Campaign\Models\CampaignTrackingLog;
use Tests\TestCase;

class GeoReportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_opens_by_country_returns_aggregated_data(): void
    {
        $this->authenticate();

        $campaign = Campaign::forceCreate([
            'name' => 'Geo Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);

        $logEs1 = CampaignTrackingLog::forceCreate([
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'campaign_id' => $campaign->id,
            'message_id' => 'msg-1',
            'status' => 'sent',
            'email' => 'test1@example.com',
        ]);

        $logEs2 = CampaignTrackingLog::forceCreate([
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12',
            'campaign_id' => $campaign->id,
            'message_id' => 'msg-2',
            'status' => 'sent',
            'email' => 'test2@example.com',
        ]);

        $logMx = CampaignTrackingLog::forceCreate([
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a13',
            'campaign_id' => $campaign->id,
            'message_id' => 'msg-3',
            'status' => 'sent',
            'email' => 'test3@example.com',
        ]);

        CampaignOpenLog::create(['tracking_log_id' => $logEs1->id, 'country' => 'ES']);
        CampaignOpenLog::create(['tracking_log_id' => $logEs1->id, 'country' => 'ES']);
        CampaignOpenLog::create(['tracking_log_id' => $logEs2->id, 'country' => 'ES']);
        CampaignOpenLog::create(['tracking_log_id' => $logMx->id, 'country' => 'MX']);

        $response = $this->getJson("/api/campaign/campaigns/{$campaign->uid}/geo/opens");
        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(2, $data);

        $es = collect($data)->firstWhere('country', 'ES');
        $mx = collect($data)->firstWhere('country', 'MX');

        $this->assertNotNull($es);
        $this->assertNotNull($mx);
        $this->assertSame(2, (int) $es['unique_opens']);
        $this->assertSame(1, (int) $mx['unique_opens']);
    }

    public function test_clicks_by_country_returns_aggregated_data(): void
    {
        $this->authenticate();

        $campaign = Campaign::forceCreate([
            'name' => 'Geo Click Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);

        $log = CampaignTrackingLog::forceCreate([
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12',
            'campaign_id' => $campaign->id,
            'message_id' => 'msg-2',
            'status' => 'sent',
            'email' => 'test@example.com',
        ]);

        CampaignClickLog::create(['tracking_log_id' => $log->id, 'country' => 'AR']);
        CampaignClickLog::create(['tracking_log_id' => $log->id, 'country' => 'AR']);
        CampaignClickLog::create(['tracking_log_id' => $log->id, 'country' => 'CO']);

        $response = $this->getJson("/api/campaign/campaigns/{$campaign->uid}/geo/clicks");
        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(2, $data);

        $ar = collect($data)->firstWhere('country', 'AR');
        $co = collect($data)->firstWhere('country', 'CO');

        $this->assertNotNull($ar);
        $this->assertNotNull($co);
        $this->assertSame(2, (int) $ar['clicks']);
        $this->assertSame(1, (int) $co['clicks']);
    }

    public function test_geo_opens_returns_404_for_missing_campaign(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/campaign/campaigns/nonexistent/geo/opens');
        $response->assertNotFound();
    }
}

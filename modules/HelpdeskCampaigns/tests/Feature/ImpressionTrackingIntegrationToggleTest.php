<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskCampaigns\Jobs\RecordImpressionJob;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Tests\TestCase;

/**
 * Regression tests for the `campaigns.integration_enabled` admin toggle
 * (Settings → Integraciones → HelpdeskCampaigns).
 *
 * ImpressionTrackingController::recordView()/recordClick() are the public,
 * unauthenticated endpoints where new impression/click metrics are recorded.
 * Both must short-circuit — without queuing a job or touching the impressions
 * table — as soon as helpdesk_campaigns_enabled() is false.
 */
class ImpressionTrackingIntegrationToggleTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['activitylog.enabled' => false]);
    }

    // ── recordView ───────────────────────────────────────────────────────────

    public function test_record_view_does_not_queue_a_job_when_integration_toggle_is_off(): void
    {
        Setting::set('campaigns.integration_enabled', '0', 'campaigns');
        Queue::fake();
        $campaign = Campaign::factory()->active()->create();

        $this->postJson('/helpdesk/campaigns/track/view', [
            'campaign_id' => $campaign->id,
            'page_url' => 'https://example.com/products',
            'device_type' => 'desktop',
        ])
            ->assertOk()
            ->assertExactJson([
                'success' => false,
                'message' => 'Campaign tracking is disabled.',
                'code' => 'INTEGRATION_DISABLED',
            ]);

        Queue::assertNotPushed(RecordImpressionJob::class);
    }

    public function test_record_view_queues_a_job_when_integration_toggle_is_on(): void
    {
        Setting::set('campaigns.integration_enabled', '1', 'campaigns');
        Queue::fake();
        $campaign = Campaign::factory()->active()->create();

        $this->postJson('/helpdesk/campaigns/track/view', [
            'campaign_id' => $campaign->id,
            'page_url' => 'https://example.com/products',
            'device_type' => 'desktop',
        ])
            ->assertOk()
            ->assertJsonStructure(['impression_id']);

        Queue::assertPushed(RecordImpressionJob::class);
    }

    public function test_record_view_queues_a_job_by_default_without_a_stored_setting(): void
    {
        Queue::fake();
        $campaign = Campaign::factory()->active()->create();

        $this->postJson('/helpdesk/campaigns/track/view', [
            'campaign_id' => $campaign->id,
            'page_url' => 'https://example.com/products',
            'device_type' => 'desktop',
        ])->assertOk();

        Queue::assertPushed(RecordImpressionJob::class);
    }

    // ── recordClick ──────────────────────────────────────────────────────────

    public function test_record_click_is_disabled_when_integration_toggle_is_off(): void
    {
        Setting::set('campaigns.integration_enabled', '0', 'campaigns');

        $this->postJson('/helpdesk/campaigns/track/click/'.(string) Str::uuid())
            ->assertOk()
            ->assertExactJson([
                'success' => false,
                'message' => 'Campaign tracking is disabled.',
                'code' => 'INTEGRATION_DISABLED',
            ]);
    }

    public function test_record_click_proceeds_past_the_guard_when_integration_toggle_is_on(): void
    {
        Setting::set('campaigns.integration_enabled', '1', 'campaigns');

        // No impression exists for this UUID, so the guarded logic proceeds
        // to the normal "not found" path instead of the disabled short-circuit.
        $this->postJson('/helpdesk/campaigns/track/click/'.(string) Str::uuid())
            ->assertStatus(404)
            ->assertJsonPath('message', 'Impression not found.');
    }
}

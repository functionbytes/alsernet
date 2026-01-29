<?php

namespace Modules\Mailing\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mailing\Enums\CampaignStatus;
use Modules\Mailing\Models\Campaign;
use Modules\Mailing\Models\CampaignAnalytics;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
        ]);

        $this->assertDatabaseHas('mailing_campaigns', [
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
        ]);

        $this->assertEquals('Test Campaign', $campaign->name);
    }

    public function test_campaign_has_default_draft_status(): void
    {
        $campaign = Campaign::factory()->create();

        $this->assertEquals(CampaignStatus::DRAFT, $campaign->status);
        $this->assertTrue($campaign->isDraft());
    }

    public function test_can_mark_campaign_as_sending(): void
    {
        $campaign = Campaign::factory()->create();

        $campaign->markAsSending();

        $this->assertEquals(CampaignStatus::SENDING, $campaign->fresh()->status);
    }

    public function test_can_mark_campaign_as_sent(): void
    {
        $campaign = Campaign::factory()->create();

        $campaign->markAsSent();

        $this->assertEquals(CampaignStatus::SENT, $campaign->fresh()->status);
        $this->assertTrue($campaign->fresh()->isSent());
        $this->assertNotNull($campaign->fresh()->sent_at);
    }

    public function test_can_mark_campaign_as_failed(): void
    {
        $campaign = Campaign::factory()->create();

        $campaign->markAsFailed();

        $this->assertEquals(CampaignStatus::FAILED, $campaign->fresh()->status);
    }

    public function test_can_schedule_campaign(): void
    {
        $campaign = Campaign::factory()->create();
        $scheduledTime = now()->addHours(2);

        $campaign->schedule($scheduledTime);

        $this->assertEquals(CampaignStatus::SCHEDULED, $campaign->fresh()->status);
        $this->assertEquals(
            $scheduledTime->timestamp,
            $campaign->fresh()->scheduled_at->timestamp
        );
    }

    public function test_campaign_metadata_is_cast_to_array(): void
    {
        $metadata = ['key' => 'value', 'tags' => ['tag1', 'tag2']];

        $campaign = Campaign::factory()->create([
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($campaign->metadata);
        $this->assertEquals($metadata, $campaign->metadata);
    }

    public function test_campaign_dates_are_cast_to_datetime(): void
    {
        $campaign = Campaign::factory()->sent()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $campaign->sent_at);
        $this->assertNotNull($campaign->sent_at);
    }

    public function test_campaign_soft_deletes(): void
    {
        $campaign = Campaign::factory()->create();
        $campaignId = $campaign->id;

        $campaign->delete();

        $this->assertSoftDeleted('mailing_campaigns', ['id' => $campaignId]);
        $this->assertNotNull($campaign->fresh()?->deleted_at);
    }

    public function test_scope_draft_returns_only_draft_campaigns(): void
    {
        Campaign::factory()->count(3)->create(['status' => CampaignStatus::DRAFT]);
        Campaign::factory()->count(2)->sent()->create();

        $drafts = Campaign::draft()->get();

        $this->assertCount(3, $drafts);
        $drafts->each(function ($campaign) {
            $this->assertEquals(CampaignStatus::DRAFT, $campaign->status);
        });
    }

    public function test_scope_scheduled_returns_only_scheduled_campaigns(): void
    {
        Campaign::factory()->count(2)->scheduled()->create();
        Campaign::factory()->count(3)->create(['status' => CampaignStatus::DRAFT]);

        $scheduled = Campaign::scheduled()->get();

        $this->assertCount(2, $scheduled);
        $scheduled->each(function ($campaign) {
            $this->assertEquals(CampaignStatus::SCHEDULED, $campaign->status);
        });
    }

    public function test_scope_sent_returns_only_sent_campaigns(): void
    {
        Campaign::factory()->count(4)->sent()->create();
        Campaign::factory()->count(1)->create(['status' => CampaignStatus::DRAFT]);

        $sent = Campaign::sent()->get();

        $this->assertCount(4, $sent);
        $sent->each(function ($campaign) {
            $this->assertEquals(CampaignStatus::SENT, $campaign->status);
        });
    }

    public function test_campaign_has_analytics_relationship(): void
    {
        $campaign = Campaign::factory()->create();
        $analytics = CampaignAnalytics::factory()->create([
            'campaign_id' => $campaign->id,
        ]);

        $this->assertInstanceOf(CampaignAnalytics::class, $campaign->analytics);
        $this->assertEquals($analytics->id, $campaign->analytics->id);
    }

    public function test_campaign_factory_states_work_correctly(): void
    {
        $scheduled = Campaign::factory()->scheduled()->create();
        $this->assertEquals(CampaignStatus::SCHEDULED, $scheduled->status);
        $this->assertNotNull($scheduled->scheduled_at);

        $sent = Campaign::factory()->sent()->create();
        $this->assertEquals(CampaignStatus::SENT, $sent->status);
        $this->assertNotNull($sent->sent_at);
        $this->assertGreaterThan(0, $sent->recipient_count);

        $sending = Campaign::factory()->sending()->create();
        $this->assertEquals(CampaignStatus::SENDING, $sending->status);

        $failed = Campaign::factory()->failed()->create();
        $this->assertEquals(CampaignStatus::FAILED, $failed->status);
    }
}

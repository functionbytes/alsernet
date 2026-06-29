<?php

namespace Modules\Campaign\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignWebhook;
use Tests\TestCase;

class WebhookApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_can_list_webhooks(): void
    {
        $this->authenticate();

        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);

        CampaignWebhook::create([
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a21',
            'campaign_id' => $campaign->id,
            'event' => 'sent',
            'url' => 'https://example.com/hook',
        ]);

        $response = $this->getJson('/api/campaign/webhooks');
        $response->assertOk();
        $response->assertJsonPath('data.data.0.event', 'sent');
    }

    public function test_can_create_webhook(): void
    {
        $this->authenticate();

        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);

        $response = $this->postJson('/api/campaign/webhooks', [
            'campaign_uid' => $campaign->uid,
            'name' => 'My Hook',
            'event' => 'opened',
            'url' => 'https://example.com/webhook',
            'method' => 'POST',
            'enabled' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('campaign_webhooks', [
            'event' => 'opened',
            'url' => 'https://example.com/webhook',
        ]);
    }

    public function test_create_webhook_returns_404_for_missing_campaign(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/campaign/webhooks', [
            'campaign_uid' => 'nonexistent',
            'event' => 'sent',
            'url' => 'https://example.com/hook',
        ]);

        $response->assertNotFound();
    }

    public function test_can_show_webhook(): void
    {
        $this->authenticate();

        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);

        $webhook = CampaignWebhook::create([
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a22',
            'campaign_id' => $campaign->id,
            'event' => 'clicked',
            'url' => 'https://example.com/c',
        ]);

        $response = $this->getJson("/api/campaign/webhooks/{$webhook->uid}");
        $response->assertOk();
        $response->assertJsonPath('data.event', 'clicked');
    }

    public function test_can_update_webhook(): void
    {
        $this->authenticate();

        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);

        $webhook = CampaignWebhook::create([
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a23',
            'campaign_id' => $campaign->id,
            'event' => 'sent',
            'url' => 'https://old.com',
        ]);

        $response = $this->putJson("/api/campaign/webhooks/{$webhook->uid}", [
            'url' => 'https://new.com',
            'enabled' => false,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('campaign_webhooks', [
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a23',
            'url' => 'https://new.com',
            'enabled' => 0,
        ]);
    }

    public function test_can_delete_webhook(): void
    {
        $this->authenticate();

        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);

        $webhook = CampaignWebhook::create([
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a24',
            'campaign_id' => $campaign->id,
            'event' => 'sent',
            'url' => 'https://example.com/d',
        ]);

        $response = $this->deleteJson("/api/campaign/webhooks/{$webhook->uid}");
        $response->assertOk();
        $this->assertDatabaseMissing('campaign_webhooks', ['uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a24']);
    }

    public function test_can_toggle_webhook(): void
    {
        $this->authenticate();

        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);

        $webhook = CampaignWebhook::create([
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a25',
            'campaign_id' => $campaign->id,
            'event' => 'sent',
            'url' => 'https://example.com/t',
            'enabled' => true,
        ]);

        $response = $this->postJson("/api/campaign/webhooks/{$webhook->uid}/toggle");
        $response->assertOk();
        $this->assertDatabaseHas('campaign_webhooks', [
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a25',
            'enabled' => 0,
        ]);
    }

    public function test_can_test_webhook(): void
    {
        Queue::fake();
        $this->authenticate();

        $campaign = Campaign::forceCreate([
            'name' => 'Test',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);

        $webhook = CampaignWebhook::create([
            'uid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a26',
            'campaign_id' => $campaign->id,
            'event' => 'sent',
            'url' => 'https://example.com/test',
        ]);

        $response = $this->postJson("/api/campaign/webhooks/{$webhook->uid}/test");
        $response->assertOk();
        $response->assertJsonPath('status', 'success');
    }
}

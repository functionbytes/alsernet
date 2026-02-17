<?php

namespace Modules\Mailrelay\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Enums\CampaignStatus;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CampaignApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected MailProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'mailrelay.campaigns.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'mailrelay.campaigns.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'mailrelay.campaigns.edit', 'guard_name' => 'web']);
        Permission::create(['name' => 'mailrelay.campaigns.delete', 'guard_name' => 'web']);
        Permission::create(['name' => 'mailrelay.campaigns.send', 'guard_name' => 'web']);

        // Create authorized user
        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'mailrelay.campaigns.view',
            'mailrelay.campaigns.create',
            'mailrelay.campaigns.edit',
            'mailrelay.campaigns.delete',
            'mailrelay.campaigns.send',
        ]);

        // Create a mail provider
        $this->provider = MailProvider::factory()->create([
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    public function test_can_list_campaigns(): void
    {
        Campaign::factory()->count(3)->create([
            'mail_provider_id' => $this->provider->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/campaigns');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'subject',
                        'status',
                    ],
                ],
                'meta',
            ]);
    }

    public function test_can_create_campaign(): void
    {
        $campaignData = [
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'html_content' => '<h1>Test Content</h1>',
            'mail_provider_id' => $this->provider->id,
            'track_opens' => true,
            'track_clicks' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/campaigns', $campaignData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'subject',
                ],
            ]);

        $this->assertDatabaseHas('mails_campaigns', [
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
        ]);
    }

    public function test_can_show_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                ],
            ]);
    }

    public function test_can_update_draft_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'status' => CampaignStatus::DRAFT,
        ]);

        $updateData = [
            'name' => 'Updated Campaign',
            'subject' => 'Updated Subject',
            'html_content' => '<h1>Updated Content</h1>',
            'mail_provider_id' => $this->provider->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/campaigns/{$campaign->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('mails_campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Campaign',
        ]);
    }

    public function test_cannot_update_sent_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'status' => CampaignStatus::SENT,
            'sent_at' => now(),
        ]);

        $updateData = [
            'name' => 'Should Not Update',
            'subject' => 'Should Not Update',
            'html_content' => '<h1>Should Not Update</h1>',
            'mail_provider_id' => $this->provider->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/campaigns/{$campaign->id}", $updateData);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('mails_campaigns', [
            'name' => 'Should Not Update',
        ]);
    }

    public function test_can_delete_draft_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'status' => CampaignStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('mails_campaigns', [
            'id' => $campaign->id,
        ]);
    }

    public function test_cannot_delete_sent_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'status' => CampaignStatus::SENT,
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('mails_campaigns', [
            'id' => $campaign->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_duplicate_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'name' => 'Original Campaign',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->id}/duplicate");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);

        $this->assertDatabaseHas('mails_campaigns', [
            'name' => 'Original Campaign (Copy)',
        ]);
    }

    public function test_can_get_campaign_preview(): void
    {
        $campaign = Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'html_content' => '<h1>Preview Content</h1>',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/campaigns/{$campaign->id}/preview");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'html',
                ],
            ]);
    }

    public function test_can_schedule_draft_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'status' => CampaignStatus::DRAFT,
        ]);

        $scheduleData = [
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'recipients' => [
                ['email' => 'test@example.com'],
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->id}/schedule", $scheduleData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('mails_campaigns', [
            'id' => $campaign->id,
        ]);
    }

    public function test_cannot_schedule_non_draft_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'status' => CampaignStatus::SENT,
        ]);

        $scheduleData = [
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'recipients' => [
                ['email' => 'test@example.com'],
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->id}/schedule", $scheduleData);

        $response->assertStatus(403);
    }

    public function test_can_filter_campaigns_by_status(): void
    {
        Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'status' => CampaignStatus::DRAFT,
        ]);

        Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'status' => CampaignStatus::SENT,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/campaigns?status=draft');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('draft', $data[0]['status']);
    }

    public function test_can_search_campaigns(): void
    {
        Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'name' => 'Newsletter Campaign',
        ]);

        Campaign::factory()->create([
            'mail_provider_id' => $this->provider->id,
            'name' => 'Promotional Campaign',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/campaigns?search=Newsletter');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Newsletter', $data[0]['name']);
    }
}

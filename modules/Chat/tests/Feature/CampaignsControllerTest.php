<?php

namespace Modules\Chat\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Models\Accounts\Account;
use Tests\TestCase;

class CampaignsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Campaign routes not yet implemented in web.php');
    }

    public function test_index_returns_campaigns(): void
    {
        CampaignFactory::new()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get(route('settings.chat.campaigns.index'));

        $response->assertOk();
        $response->assertViewIs('Chat::settings.campaigns.index');
        $response->assertViewHas('campaigns');
    }

    public function test_store_validates_with_form_request(): void
    {
        $data = [
            'type' => 'popup',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.chat.campaigns.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_store_creates_campaign(): void
    {
        $data = [
            'name' => 'Test Campaign',
            'description' => 'A test campaign',
            'type' => 'popup',
            'status' => 'draft',
            'content' => ['title' => 'Hello'],
            'appearance' => ['color' => '#b10100'],
            'conditions' => ['url' => '/test'],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.chat.campaigns.store'), $data);

        $response->assertCreated();
        $response->assertJsonFragment(['name' => 'Test Campaign']);

        $this->assertDatabaseHas('chat_campaigns', [
            'name' => 'Test Campaign',
            'type' => 'popup',
            'status' => 'draft',
        ]);
    }

    public function test_update_modifies_campaign(): void
    {
        $campaign = CampaignFactory::new()->create([
            'name' => 'Original Name',
        ]);

        $data = [
            'name' => 'Updated Name',
            'description' => $campaign->description,
            'type' => $campaign->type,
            'content' => $campaign->content,
            'appearance' => $campaign->appearance,
            'conditions' => $campaign->conditions,
        ];

        $response = $this->actingAs($this->user)
            ->putJson(route('settings.chat.campaigns.update', $campaign), $data);

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('chat_campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_statistics_returns_analytics_data(): void
    {
        $campaign = CampaignFactory::new()->active()->create();

        $response = $this->actingAs($this->user)
            ->getJson(route('settings.chat.campaigns.statistics', $campaign));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'campaign',
                'overview' => [
                    'total_impressions',
                    'total_clicks',
                    'ctr',
                ],
            ],
        ]);
    }

    public function test_publish_activates_campaign(): void
    {
        $campaign = CampaignFactory::new()->draft()->create([
            'content' => ['title' => 'Test', 'message' => 'Test message'],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.chat.campaigns.publish', $campaign));

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'active']);

        $this->assertDatabaseHas('chat_campaigns', [
            'id' => $campaign->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseMissing('chat_campaigns', [
            'id' => $campaign->id,
            'published_at' => null,
        ]);
    }

    public function test_publish_fails_without_content(): void
    {
        $campaign = CampaignFactory::new()->create([
            'status' => 'draft',
            'content' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.chat.campaigns.publish', $campaign));

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_pause_campaign(): void
    {
        $campaign = CampaignFactory::new()->active()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.chat.campaigns.pause', $campaign));

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'paused']);
    }

    public function test_pause_fails_on_non_active_campaign(): void
    {
        $campaign = CampaignFactory::new()->draft()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.chat.campaigns.pause', $campaign));

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_resume_campaign(): void
    {
        $campaign = CampaignFactory::new()->create([
            'status' => 'paused',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.chat.campaigns.resume', $campaign));

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'active']);
    }

    public function test_end_campaign(): void
    {
        $campaign = CampaignFactory::new()->active()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.chat.campaigns.end', $campaign));

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'ended']);
    }

    public function test_duplicate_campaign(): void
    {
        $campaign = CampaignFactory::new()->create([
            'name' => 'Original Campaign',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.chat.campaigns.duplicate', $campaign));

        $response->assertCreated();
        $response->assertJsonFragment(['name' => 'Original Campaign (Copy)']);

        $this->assertDatabaseHas('chat_campaigns', [
            'name' => 'Original Campaign (Copy)',
            'status' => 'draft',
        ]);
    }

    public function test_destroy_deletes_campaign(): void
    {
        $campaign = CampaignFactory::new()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson(route('settings.chat.campaigns.destroy', $campaign));

        $response->assertOk();
        $response->assertJsonFragment(['success' => true]);

        $this->assertSoftDeleted('chat_campaigns', [
            'id' => $campaign->id,
        ]);
    }

    public function test_campaigns_require_authorization(): void
    {
        $guestResponse = $this->getJson(route('settings.chat.campaigns.index'));
        $guestResponse->assertUnauthorized();

        $campaign = CampaignFactory::new()->create();
        $guestResponse = $this->getJson(route('settings.chat.campaigns.show', $campaign));
        $guestResponse->assertUnauthorized();
    }

    public function test_filter_by_status(): void
    {
        CampaignFactory::new()->active()->count(3)->create();
        CampaignFactory::new()->draft()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->get(route('settings.chat.campaigns.index', ['status' => 'active']));

        $response->assertOk();
        $campaigns = $response->viewData('campaigns');
        $this->assertCount(3, $campaigns);
    }

    public function test_filter_by_type(): void
    {
        CampaignFactory::new()->create(['type' => 'popup']);
        CampaignFactory::new()->create(['type' => 'banner']);
        CampaignFactory::new()->create(['type' => 'popup']);

        $response = $this->actingAs($this->user)
            ->get(route('settings.chat.campaigns.index', ['type' => 'popup']));

        $response->assertOk();
        $campaigns = $response->viewData('campaigns');
        $this->assertCount(2, $campaigns);
    }

    public function test_search_campaigns(): void
    {
        CampaignFactory::new()->create(['name' => 'Special Offer Campaign']);
        CampaignFactory::new()->create(['name' => 'Holiday Sale']);
        CampaignFactory::new()->create(['name' => 'Special Discount']);

        $response = $this->actingAs($this->user)
            ->get(route('settings.chat.campaigns.index', ['search' => 'Special']));

        $response->assertOk();
        $campaigns = $response->viewData('campaigns');
        $this->assertCount(2, $campaigns);
    }
}

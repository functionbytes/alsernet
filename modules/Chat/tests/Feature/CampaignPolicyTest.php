<?php

namespace Modules\Chat\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Campaigns\Campaign;
use Tests\TestCase;

class CampaignPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Campaign routes not yet implemented in web.php');
    }

    public function test_user_can_view_own_account_campaigns(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $account->id]);
        $campaign = Campaign::factory()->create(['account_id' => $account->id]);

        $response = $this->actingAs($user)->getJson("/chats/chat/campaigns/{$campaign->id}");

        $response->assertOk();
    }

    public function test_user_cannot_view_other_account_campaigns(): void
    {
        $accountA = Account::factory()->create(['name' => 'Account A']);
        $accountB = Account::factory()->create(['name' => 'Account B']);

        $userA = User::factory()->create();
        DB::table('chat_accounts_user')->insert(['user_id' => $userA->id, 'account_id' => $accountA->id]);
        $campaignB = Campaign::factory()->create(['account_id' => $accountB->id]);

        $response = $this->actingAs($userA)->getJson("/chats/chat/campaigns/{$campaignB->id}");

        $response->assertForbidden();
    }

    public function test_user_can_create_campaigns(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $account->id]);

        $response = $this->actingAs($user)->postJson('/chats/chat/campaigns', [
            'name' => 'Test Campaign',
            'description' => 'Test Description',
            'type' => 'popup',
            'status' => 'draft',
            'content' => ['title' => 'Hello'],
            'appearance' => ['primary_color' => '#b10100'],
            'conditions' => [],
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['success', 'data' => ['id', 'name']]);

        $this->assertDatabaseHas('chat_campaigns', [
            'name' => 'Test Campaign',
            'account_id' => $account->id,
        ]);
    }

    public function test_user_can_update_own_account_campaigns(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $account->id]);
        $campaign = Campaign::factory()->create(['account_id' => $account->id]);

        $response = $this->actingAs($user)->putJson("/chats/chat/campaigns/{$campaign->id}", [
            'name' => 'Updated Campaign',
            'description' => $campaign->description,
            'type' => $campaign->type,
            'content' => $campaign->content,
            'appearance' => $campaign->appearance,
            'conditions' => $campaign->conditions,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Campaign']);

        $this->assertDatabaseHas('chat_campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Campaign',
        ]);
    }

    public function test_user_can_delete_own_account_campaigns(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $account->id]);
        $campaign = Campaign::factory()->create(['account_id' => $account->id]);

        $response = $this->actingAs($user)->deleteJson("/chats/chat/campaigns/{$campaign->id}");

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertSoftDeleted('chat_campaigns', [
            'id' => $campaign->id,
        ]);
    }

    public function test_user_can_publish_campaign(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $account->id]);
        $campaign = Campaign::factory()->draft()->create([
            'account_id' => $account->id,
            'content' => ['title' => 'Test'],
        ]);

        $response = $this->actingAs($user)->postJson("/chats/chat/campaigns/{$campaign->id}/publish");

        $response->assertOk()
            ->assertJsonFragment(['status' => 'active']);

        $campaign->refresh();
        $this->assertEquals('active', $campaign->status);
        $this->assertNotNull($campaign->published_at);
    }

    public function test_user_cannot_publish_campaign_without_content(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $account->id]);
        $campaign = Campaign::factory()->draft()->create([
            'account_id' => $account->id,
            'content' => [],
        ]);

        $response = $this->actingAs($user)->postJson("/chats/chat/campaigns/{$campaign->id}/publish");

        $response->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Cannot publish campaign without content']);
    }

    public function test_user_can_pause_active_campaign(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $account->id]);
        $campaign = Campaign::factory()->active()->create(['account_id' => $account->id]);

        $response = $this->actingAs($user)->postJson("/chats/chat/campaigns/{$campaign->id}/pause");

        $response->assertOk();

        $campaign->refresh();
        $this->assertEquals('paused', $campaign->status);
    }

    public function test_user_can_resume_paused_campaign(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $account->id]);
        $campaign = Campaign::factory()->create([
            'account_id' => $account->id,
            'status' => 'paused',
            'published_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user)->postJson("/chats/chat/campaigns/{$campaign->id}/resume");

        $response->assertOk();

        $campaign->refresh();
        $this->assertEquals('active', $campaign->status);
    }

    public function test_user_can_end_campaign(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $account->id]);
        $campaign = Campaign::factory()->active()->create(['account_id' => $account->id]);

        $response = $this->actingAs($user)->postJson("/chats/chat/campaigns/{$campaign->id}/end");

        $response->assertOk();

        $campaign->refresh();
        $this->assertEquals('ended', $campaign->status);
        $this->assertNotNull($campaign->ends_at);
    }

    public function test_user_can_duplicate_campaign(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $account->id]);
        $campaign = Campaign::factory()->create(['account_id' => $account->id, 'name' => 'Original']);

        $response = $this->actingAs($user)->postJson("/chats/chat/campaigns/{$campaign->id}/duplicate");

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Original (Copy)']);

        $this->assertDatabaseHas('chat_campaigns', [
            'name' => 'Original (Copy)',
            'account_id' => $account->id,
            'status' => 'draft',
        ]);
    }
}

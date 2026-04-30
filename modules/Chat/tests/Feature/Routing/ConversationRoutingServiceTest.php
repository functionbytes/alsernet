<?php

namespace Modules\Chat\Tests\Feature\Routing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Inbox\Inbox;
use Modules\Chat\Models\Labels\Label;
use Modules\Chat\Models\Teams\Team;
use Modules\Chat\Services\ConversationRoutingService;
use Tests\TestCase;

class ConversationRoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;

    protected Inbox $inbox;

    protected ConversationRoutingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->inbox = Inbox::factory()->create(['account_id' => $this->account->id]);
        $this->service = new ConversationRoutingService;

        ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Open',
            'slug' => 'open',
            'order' => 1,
        ]);

        ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Resolved',
            'slug' => 'resolved',
            'order' => 2,
        ]);
    }

    public function test_auto_assign_returns_null_when_no_agents_available(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $agent = $this->service->autoAssign($conversation);

        $this->assertNull($agent);
    }

    public function test_auto_assign_assigns_to_single_available_agent(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $this->account->id]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $agent = $this->service->autoAssign($conversation);

        $this->assertEquals($user->id, $agent->id);
        $this->assertDatabaseHas('chat_conversations', [
            'id' => $conversation->id,
            'assignee_id' => $user->id,
        ]);
    }

    public function test_auto_assign_skips_inactive_agents(): void
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);
        DB::table('chat_accounts_user')->insert(['user_id' => $inactiveUser->id, 'account_id' => $this->account->id]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $agent = $this->service->autoAssign($conversation);

        $this->assertNull($agent);
    }

    public function test_round_robin_distributes_conversations_evenly(): void
    {
        $user1 = User::factory()->create(['is_active' => true]);
        $user2 = User::factory()->create(['is_active' => true]);
        $user3 = User::factory()->create(['is_active' => true]);

        DB::table('chat_accounts_user')->insert([
            ['user_id' => $user1->id, 'account_id' => $this->account->id],
            ['user_id' => $user2->id, 'account_id' => $this->account->id],
            ['user_id' => $user3->id, 'account_id' => $this->account->id],
        ]);

        $conv1 = Conversation::factory()->create(['account_id' => $this->account->id, 'inbox_id' => $this->inbox->id]);
        $conv2 = Conversation::factory()->create(['account_id' => $this->account->id, 'inbox_id' => $this->inbox->id]);
        $conv3 = Conversation::factory()->create(['account_id' => $this->account->id, 'inbox_id' => $this->inbox->id]);
        $conv4 = Conversation::factory()->create(['account_id' => $this->account->id, 'inbox_id' => $this->inbox->id]);

        $agent1 = $this->service->autoAssign($conv1, ConversationRoutingService::STRATEGY_ROUND_ROBIN);
        $agent2 = $this->service->autoAssign($conv2, ConversationRoutingService::STRATEGY_ROUND_ROBIN);
        $agent3 = $this->service->autoAssign($conv3, ConversationRoutingService::STRATEGY_ROUND_ROBIN);
        $agent4 = $this->service->autoAssign($conv4, ConversationRoutingService::STRATEGY_ROUND_ROBIN);

        $this->assertNotEquals($agent1->id, $agent2->id);
        $this->assertNotEquals($agent2->id, $agent3->id);
        $this->assertEquals($agent1->id, $agent4->id);
    }

    public function test_least_busy_assigns_to_agent_with_fewer_conversations(): void
    {
        $user1 = User::factory()->create(['is_active' => true]);
        $user2 = User::factory()->create(['is_active' => true]);

        DB::table('chat_accounts_user')->insert([
            ['user_id' => $user1->id, 'account_id' => $this->account->id],
            ['user_id' => $user2->id, 'account_id' => $this->account->id],
        ]);

        $openStatus = ConversationStatus::where('slug', 'open')->first();

        Conversation::factory()->count(3)->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'assignee_id' => $user1->id,
            'status_id' => $openStatus->id,
        ]);

        Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'assignee_id' => $user2->id,
            'status_id' => $openStatus->id,
        ]);

        $newConversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $agent = $this->service->autoAssign($newConversation, ConversationRoutingService::STRATEGY_LEAST_BUSY);

        $this->assertEquals($user2->id, $agent->id);
    }

    public function test_balanced_assignment_considers_workload(): void
    {
        $user1 = User::factory()->create(['is_active' => true]);
        $user2 = User::factory()->create(['is_active' => true]);

        DB::table('chat_accounts_user')->insert([
            ['user_id' => $user1->id, 'account_id' => $this->account->id],
            ['user_id' => $user2->id, 'account_id' => $this->account->id],
        ]);

        $openStatus = ConversationStatus::where('slug', 'open')->first();

        Conversation::factory()->count(5)->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'assignee_id' => $user1->id,
            'status_id' => $openStatus->id,
        ]);

        $newConversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $agent = $this->service->autoAssign($newConversation, ConversationRoutingService::STRATEGY_BALANCED);

        $this->assertEquals($user2->id, $agent->id);
    }

    public function test_skilled_assignment_prefers_agent_with_matching_expertise(): void
    {
        $user1 = User::factory()->create(['is_active' => true]);
        $user2 = User::factory()->create(['is_active' => true]);

        DB::table('chat_accounts_user')->insert([
            ['user_id' => $user1->id, 'account_id' => $this->account->id],
            ['user_id' => $user2->id, 'account_id' => $this->account->id],
        ]);

        $label = Label::create([
            'account_id' => $this->account->id,
            'title' => 'Technical Support',
            'color' => '#b10100',
        ]);

        $resolvedStatus = ConversationStatus::where('slug', 'resolved')->first();

        $pastConversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'assignee_id' => $user1->id,
            'status_id' => $resolvedStatus->id,
        ]);
        $pastConversation->labels()->attach($label->id);

        $newConversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);
        $newConversation->labels()->attach($label->id);

        $agent = $this->service->autoAssign($newConversation, ConversationRoutingService::STRATEGY_SKILLED);

        $this->assertEquals($user1->id, $agent->id);
    }

    public function test_skilled_assignment_falls_back_to_balanced_without_labels(): void
    {
        $user1 = User::factory()->create(['is_active' => true]);
        $user2 = User::factory()->create(['is_active' => true]);

        DB::table('chat_accounts_user')->insert([
            ['user_id' => $user1->id, 'account_id' => $this->account->id],
            ['user_id' => $user2->id, 'account_id' => $this->account->id],
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $agent = $this->service->autoAssign($conversation, ConversationRoutingService::STRATEGY_SKILLED);

        $this->assertNotNull($agent);
        $this->assertContains($agent->id, [$user1->id, $user2->id]);
    }

    public function test_availability_based_assignment_prefers_online_agents(): void
    {
        $onlineUser = User::factory()->create([
            'is_active' => true,
            'last_seen_at' => now()->subMinutes(2),
        ]);

        $offlineUser = User::factory()->create([
            'is_active' => true,
            'last_seen_at' => now()->subMinutes(30),
        ]);

        DB::table('chat_accounts_user')->insert([
            ['user_id' => $onlineUser->id, 'account_id' => $this->account->id],
            ['user_id' => $offlineUser->id, 'account_id' => $this->account->id],
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $agent = $this->service->autoAssign($conversation, ConversationRoutingService::STRATEGY_AVAILABILITY);

        $this->assertEquals($onlineUser->id, $agent->id);
    }

    public function test_auto_assign_respects_team_assignment(): void
    {
        $team = Team::create([
            'account_id' => $this->account->id,
            'name' => 'Support Team',
        ]);

        $this->inbox->teams()->attach($team->id);

        $teamMember = User::factory()->create(['is_active' => true]);
        $nonTeamMember = User::factory()->create(['is_active' => true]);

        DB::table('chat_accounts_user')->insert([
            ['user_id' => $teamMember->id, 'account_id' => $this->account->id],
            ['user_id' => $nonTeamMember->id, 'account_id' => $this->account->id],
        ]);

        $team->members()->attach($teamMember->id);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $agent = $this->service->autoAssign($conversation);

        $this->assertEquals($teamMember->id, $agent->id);
    }

    public function test_suggest_agent_returns_recommendation(): void
    {
        $user = User::factory()->create(['is_active' => true, 'name' => 'John Doe', 'email' => 'john@example.com']);
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $this->account->id]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $suggestion = $this->service->suggestAgent($conversation);

        $this->assertNotNull($suggestion);
        $this->assertEquals($user->id, $suggestion['agent_id']);
        $this->assertEquals('John Doe', $suggestion['agent_name']);
        $this->assertEquals('john@example.com', $suggestion['agent_email']);
        $this->assertEquals(0, $suggestion['active_conversations']);
        $this->assertNotEmpty($suggestion['reason']);
    }

    public function test_suggest_agent_returns_null_when_no_agents(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $suggestion = $this->service->suggestAgent($conversation);

        $this->assertNull($suggestion);
    }

    public function test_get_routing_stats_returns_correct_metrics(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $this->account->id]);

        Conversation::factory()->count(5)->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'assignee_id' => $user->id,
            'auto_assigned' => true,
            'first_assigned_at' => now(),
        ]);

        Conversation::factory()->count(3)->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'assignee_id' => $user->id,
            'auto_assigned' => false,
            'first_assigned_at' => now(),
        ]);

        $stats = $this->service->getRoutingStats($this->account->id);

        $this->assertEquals(8, $stats['total_assigned']);
        $this->assertEquals(5, $stats['auto_assigned']);
        $this->assertEquals(3, $stats['manual_assigned']);
        $this->assertEquals(62.5, $stats['auto_assignment_rate']);
    }

    public function test_get_routing_stats_filters_by_inbox(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        DB::table('chat_accounts_user')->insert(['user_id' => $user->id, 'account_id' => $this->account->id]);

        $otherInbox = Inbox::factory()->create(['account_id' => $this->account->id]);

        Conversation::factory()->count(3)->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'assignee_id' => $user->id,
        ]);

        Conversation::factory()->count(2)->create([
            'account_id' => $this->account->id,
            'inbox_id' => $otherInbox->id,
            'assignee_id' => $user->id,
        ]);

        $stats = $this->service->getRoutingStats($this->account->id, $this->inbox->id);

        $this->assertEquals(3, $stats['total_assigned']);
    }

    public function test_auto_assign_defaults_to_balanced_strategy(): void
    {
        $user1 = User::factory()->create(['is_active' => true]);
        $user2 = User::factory()->create(['is_active' => true]);

        DB::table('chat_accounts_user')->insert([
            ['user_id' => $user1->id, 'account_id' => $this->account->id],
            ['user_id' => $user2->id, 'account_id' => $this->account->id],
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $agent = $this->service->autoAssign($conversation);

        $this->assertNotNull($agent);
        $this->assertContains($agent->id, [$user1->id, $user2->id]);
    }

    public function test_routing_does_not_leak_across_accounts(): void
    {
        $otherAccount = Account::factory()->create();

        $user1 = User::factory()->create(['is_active' => true]);
        $user2 = User::factory()->create(['is_active' => true]);

        DB::table('chat_accounts_user')->insert([
            ['user_id' => $user1->id, 'account_id' => $this->account->id],
            ['user_id' => $user2->id, 'account_id' => $otherAccount->id],
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
        ]);

        $agent = $this->service->autoAssign($conversation);

        $this->assertEquals($user1->id, $agent->id);
        $this->assertNotEquals($user2->id, $agent->id);
    }
}

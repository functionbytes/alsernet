<?php

namespace Modules\Chat\Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Conversations\ConversationPriority;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Inbox\Inbox;
use Tests\TestCase;

class ConversationScopeTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::create([
            'name' => 'Test Account',
            'default_locale' => 'en',
        ]);
    }

    public function test_scope_for_account_filters_by_account_id(): void
    {
        $otherAccount = Account::create([
            'name' => 'Other Account',
            'default_locale' => 'en',
        ]);

        $customer1 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer 1',
            'email' => 'customer1@test.com',
        ]);

        $customer2 = Customer::create([
            'account_id' => $otherAccount->id,
            'name' => 'Customer 2',
            'email' => 'customer2@test.com',
        ]);

        $conversation1 = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer1->id,
        ]);

        $conversation2 = Conversation::create([
            'account_id' => $otherAccount->id,
            'customer_id' => $customer2->id,
        ]);

        $results = Conversation::forAccount($this->account->id)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($conversation1));
        $this->assertFalse($results->contains($conversation2));
    }

    public function test_scope_with_status_slug_joins_and_filters(): void
    {
        $openStatus = ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Open',
            'slug' => 'open',
            'is_open' => true,
        ]);

        $closedStatus = ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Closed',
            'slug' => 'closed',
            'is_open' => false,
        ]);

        $customer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer',
            'email' => 'customer@test.com',
        ]);

        $openConversation = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'status_id' => $openStatus->id,
        ]);

        $closedConversation = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'status_id' => $closedStatus->id,
        ]);

        $results = Conversation::withStatusSlug('open')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($openConversation->id, $results->first()->id);
    }

    public function test_scope_with_priority_slug_joins_and_filters(): void
    {
        $urgentPriority = ConversationPriority::create([
            'account_id' => $this->account->id,
            'name' => 'Urgent',
            'slug' => 'urgent',
            'order' => 1,
        ]);

        $lowPriority = ConversationPriority::create([
            'account_id' => $this->account->id,
            'name' => 'Low',
            'slug' => 'low',
            'order' => 4,
        ]);

        $customer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer',
            'email' => 'customer@test.com',
        ]);

        $urgentConversation = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'priority_id' => $urgentPriority->id,
        ]);

        $lowConversation = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'priority_id' => $lowPriority->id,
        ]);

        $results = Conversation::withPrioritySlug('urgent')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($urgentConversation->id, $results->first()->id);
    }

    public function test_scope_assigned_to_filters_by_assignee(): void
    {
        $user1 = User::create([
            'name' => 'Agent 1',
            'email' => 'agent1@test.com',
            'password' => bcrypt('password'),
        ]);

        $user2 = User::create([
            'name' => 'Agent 2',
            'email' => 'agent2@test.com',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer',
            'email' => 'customer@test.com',
        ]);

        $conversation1 = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'assignee_id' => $user1->id,
        ]);

        $conversation2 = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'assignee_id' => $user2->id,
        ]);

        $results = Conversation::assignedTo($user1->id)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($conversation1));
        $this->assertFalse($results->contains($conversation2));
    }

    public function test_scope_unassigned_filters_null_assignee(): void
    {
        $user = User::create([
            'name' => 'Agent',
            'email' => 'agent@test.com',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer',
            'email' => 'customer@test.com',
        ]);

        $assignedConversation = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'assignee_id' => $user->id,
        ]);

        $unassignedConversation = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'assignee_id' => null,
        ]);

        $results = Conversation::unassigned()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($unassignedConversation));
        $this->assertFalse($results->contains($assignedConversation));
    }

    public function test_scope_mentioning_finds_mentioned_conversations(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer',
            'email' => 'customer@test.com',
        ]);

        $inbox = Inbox::create([
            'account_id' => $this->account->id,
            'name' => 'Test Inbox',
            'channel_type' => 'web',
            'channel_id' => 1,
        ]);

        $conversationWithMention = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
        ]);

        $conversationWithoutMention = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inbox->id,
            'conversation_id' => $conversationWithMention->id,
            'message_type' => 'outgoing',
            'content' => 'Hey @'.$user->id.' can you help?',
            'sender_type' => User::class,
            'sender_id' => $user->id,
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inbox->id,
            'conversation_id' => $conversationWithoutMention->id,
            'message_type' => 'outgoing',
            'content' => 'Regular message',
            'sender_type' => User::class,
            'sender_id' => $user->id,
        ]);

        $results = Conversation::mentioning($user->id, $user->name)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($conversationWithMention));
        $this->assertFalse($results->contains($conversationWithoutMention));
    }

    public function test_scope_unattended_finds_no_agent_reply(): void
    {
        $resolvedStatus = ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Resolved',
            'slug' => 'resolved',
            'is_open' => false,
        ]);

        $openStatus = ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Open',
            'slug' => 'open',
            'is_open' => true,
        ]);

        $user = User::create([
            'name' => 'Agent',
            'email' => 'agent@test.com',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer',
            'email' => 'customer@test.com',
        ]);

        $inbox = Inbox::create([
            'account_id' => $this->account->id,
            'name' => 'Test Inbox',
            'channel_type' => 'web',
            'channel_id' => 1,
        ]);

        $unattendedConversation = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'status_id' => $openStatus->id,
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inbox->id,
            'conversation_id' => $unattendedConversation->id,
            'message_type' => 'incoming',
            'content' => 'Help needed',
            'sender_type' => Customer::class,
            'sender_id' => $customer->id,
        ]);

        $attendedConversation = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'status_id' => $openStatus->id,
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inbox->id,
            'conversation_id' => $attendedConversation->id,
            'message_type' => 'incoming',
            'content' => 'Help needed',
            'sender_type' => Customer::class,
            'sender_id' => $customer->id,
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inbox->id,
            'conversation_id' => $attendedConversation->id,
            'message_type' => 'outgoing',
            'content' => 'How can I help?',
            'sender_type' => User::class,
            'sender_id' => $user->id,
        ]);

        $results = Conversation::unattended()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($unattendedConversation));
        $this->assertFalse($results->contains($attendedConversation));
    }

    public function test_scope_text_search_matches_customer_and_content(): void
    {
        $customer1 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'John Smith',
            'email' => 'john@test.com',
        ]);

        $customer2 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Jane Doe',
            'email' => 'jane@test.com',
        ]);

        $conversation1 = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer1->id,
        ]);

        $conversation2 = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer2->id,
        ]);

        $inbox = Inbox::create([
            'account_id' => $this->account->id,
            'name' => 'Test Inbox',
            'channel_type' => 'web',
            'channel_id' => 1,
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inbox->id,
            'conversation_id' => $conversation2->id,
            'message_type' => 'incoming',
            'content' => 'I need help with my order',
            'sender_type' => Customer::class,
            'sender_id' => $customer2->id,
        ]);

        $results = Conversation::textSearch('John')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($conversation1));

        $resultsEmail = Conversation::textSearch('jane@test.com')->get();

        $this->assertCount(1, $resultsEmail);
        $this->assertTrue($resultsEmail->contains($conversation2));

        $resultsContent = Conversation::textSearch('order')->get();

        $this->assertCount(1, $resultsContent);
        $this->assertTrue($resultsContent->contains($conversation2));
    }

    public function test_scope_created_between_filters_date_range(): void
    {
        $customer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer',
            'email' => 'customer@test.com',
        ]);

        $oldDate = now()->subDays(10);
        $recentDate = now()->subDays(3);

        $oldConversation = new Conversation([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
        ]);
        $oldConversation->created_at = $oldDate;
        $oldConversation->updated_at = $oldDate;
        $oldConversation->save();

        $recentConversation = new Conversation([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
        ]);
        $recentConversation->created_at = $recentDate;
        $recentConversation->updated_at = $recentDate;
        $recentConversation->save();

        $results = Conversation::createdBetween(
            now()->subDays(5)->toDateString(),
            now()->subDays(1)->toDateString()
        )->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($recentConversation));
        $this->assertFalse($results->contains($oldConversation));
    }

    public function test_scope_aggregated_filter_counts_returns_all_counts(): void
    {
        $openStatus = ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Open',
            'slug' => 'open',
            'is_open' => true,
        ]);

        $resolvedStatus = ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Resolved',
            'slug' => 'resolved',
            'is_open' => false,
        ]);

        $urgentPriority = ConversationPriority::create([
            'account_id' => $this->account->id,
            'name' => 'Urgent',
            'slug' => 'urgent',
            'order' => 1,
        ]);

        $user = User::create([
            'name' => 'Agent',
            'email' => 'agent@test.com',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer',
            'email' => 'customer@test.com',
        ]);

        Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'status_id' => $openStatus->id,
            'priority_id' => $urgentPriority->id,
            'assignee_id' => $user->id,
        ]);

        Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'status_id' => $resolvedStatus->id,
            'assignee_id' => null,
        ]);

        $result = Conversation::aggregatedFilterCounts($user->id)->first();

        $this->assertEquals(2, $result->total);
        $this->assertEquals(1, $result->open_count);
        $this->assertEquals(1, $result->resolved_count);
        $this->assertEquals(1, $result->mine_count);
        $this->assertEquals(1, $result->unassigned_count);
        $this->assertEquals(1, $result->urgent_count);
    }

    public function test_scope_with_common_relations_eager_loads(): void
    {
        $status = ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Open',
            'slug' => 'open',
            'is_open' => true,
        ]);

        $priority = ConversationPriority::create([
            'account_id' => $this->account->id,
            'name' => 'Medium',
            'slug' => 'medium',
            'order' => 2,
        ]);

        $user = User::create([
            'name' => 'Agent',
            'email' => 'agent@test.com',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer',
            'email' => 'customer@test.com',
        ]);

        $conversation = Conversation::create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'priority_id' => $priority->id,
            'assignee_id' => $user->id,
        ]);

        $result = Conversation::withCommonRelations()->first();

        $this->assertTrue($result->relationLoaded('customer'));
        $this->assertTrue($result->relationLoaded('assignee'));
        $this->assertTrue($result->relationLoaded('status'));
        $this->assertTrue($result->relationLoaded('priority'));
        $this->assertTrue($result->relationLoaded('messages'));
    }
}

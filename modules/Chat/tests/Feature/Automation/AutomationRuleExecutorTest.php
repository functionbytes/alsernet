<?php

namespace Modules\Chat\Tests\Feature\Automation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Automations\Automation;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationPriority;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Inbox\Inbox;
use Modules\Chat\Models\Teams\Team;
use Modules\Chat\Notifications\AutomationTeamEmailNotification;
use Modules\Chat\Services\Automation\AutomationRuleExecutor;
use Modules\Chat\Services\Conversations\ConversationLabelService;
use Tests\TestCase;

class AutomationRuleExecutorTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;

    protected AutomationRuleExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->executor = new AutomationRuleExecutor(new ConversationLabelService);

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

        ConversationPriority::create([
            'account_id' => $this->account->id,
            'name' => 'High',
            'slug' => 'high',
            'order' => 1,
        ]);
    }

    public function test_execute_runs_active_rules_for_event(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([])
            ->withActions([
                ['action' => 'change_status', 'value' => 'resolved'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $resolvedStatus = ConversationStatus::where('slug', 'resolved')->first();
        $this->assertEquals($resolvedStatus->id, $conversation->status_id);
    }

    public function test_execute_skips_inactive_rules(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->inactive()
            ->forEvent('conversation_created')
            ->withActions([
                ['action' => 'change_status', 'value' => 'resolved'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertNull($conversation->status_id);
    }

    public function test_execute_skips_rules_for_different_event(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_updated')
            ->withActions([
                ['action' => 'change_status', 'value' => 'resolved'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertNull($conversation->status_id);
    }

    public function test_condition_equals_operator(): void
    {
        $inbox = Inbox::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $inbox->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'inbox_id', 'operator' => 'equals', 'value' => $inbox->id],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $highPriority = ConversationPriority::where('slug', 'high')->first();
        $this->assertEquals($highPriority->id, $conversation->priority_id);
    }

    public function test_condition_not_equals_operator(): void
    {
        $inbox = Inbox::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $inbox->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'inbox_id', 'operator' => 'not_equals', 'value' => 999],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $highPriority = ConversationPriority::where('slug', 'high')->first();
        $this->assertEquals($highPriority->id, $conversation->priority_id);
    }

    public function test_condition_contains_operator(): void
    {
        $customer = Customer::factory()->create([
            'account_id' => $this->account->id,
            'email' => 'vip@example.com',
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'contact_email', 'operator' => 'contains', 'value' => 'vip'],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $highPriority = ConversationPriority::where('slug', 'high')->first();
        $this->assertEquals($highPriority->id, $conversation->priority_id);
    }

    public function test_condition_not_contains_operator(): void
    {
        $customer = Customer::factory()->create([
            'account_id' => $this->account->id,
            'email' => 'regular@example.com',
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'contact_email', 'operator' => 'not_contains', 'value' => 'vip'],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $highPriority = ConversationPriority::where('slug', 'high')->first();
        $this->assertEquals($highPriority->id, $conversation->priority_id);
    }

    public function test_condition_is_present_operator(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::factory()->assigned($user->id)->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'assignee_id', 'operator' => 'is_present'],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $highPriority = ConversationPriority::where('slug', 'high')->first();
        $this->assertEquals($highPriority->id, $conversation->priority_id);
    }

    public function test_condition_is_not_present_operator(): void
    {
        $conversation = Conversation::factory()->unassigned()->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'assignee_id', 'operator' => 'is_not_present'],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $highPriority = ConversationPriority::where('slug', 'high')->first();
        $this->assertEquals($highPriority->id, $conversation->priority_id);
    }

    public function test_condition_greater_than_operator(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'custom_attributes' => ['order_value' => 1000],
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'order_value', 'operator' => 'greater_than', 'value' => 500],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $highPriority = ConversationPriority::where('slug', 'high')->first();
        $this->assertEquals($highPriority->id, $conversation->priority_id);
    }

    public function test_condition_less_than_operator(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'custom_attributes' => ['order_value' => 50],
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'order_value', 'operator' => 'less_than', 'value' => 100],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $highPriority = ConversationPriority::where('slug', 'high')->first();
        $this->assertEquals($highPriority->id, $conversation->priority_id);
    }

    public function test_multiple_conditions_with_and_logic(): void
    {
        $inbox = Inbox::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $customer = Customer::factory()->create([
            'account_id' => $this->account->id,
            'email' => 'vip@example.com',
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $inbox->id,
            'customer_id' => $customer->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'inbox_id', 'operator' => 'equals', 'value' => $inbox->id],
                ['attribute' => 'contact_email', 'operator' => 'contains', 'value' => 'vip'],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $highPriority = ConversationPriority::where('slug', 'high')->first();
        $this->assertEquals($highPriority->id, $conversation->priority_id);
    }

    public function test_multiple_conditions_fails_if_one_condition_not_met(): void
    {
        $inbox = Inbox::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $customer = Customer::factory()->create([
            'account_id' => $this->account->id,
            'email' => 'regular@example.com',
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $inbox->id,
            'customer_id' => $customer->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'inbox_id', 'operator' => 'equals', 'value' => $inbox->id],
                ['attribute' => 'contact_email', 'operator' => 'contains', 'value' => 'vip'],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertNull($conversation->priority_id);
    }

    public function test_action_assign_agent(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::factory()->unassigned()->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withActions([
                ['action' => 'assign_agent', 'value' => $user->id],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertEquals($user->id, $conversation->assignee_id);
    }

    public function test_action_assign_team(): void
    {
        $team = Team::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withActions([
                ['action' => 'assign_team', 'value' => $team->id],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertEquals($team->id, $conversation->team_id);
    }

    public function test_action_change_status(): void
    {
        $openStatus = ConversationStatus::where('slug', 'open')->first();
        $resolvedStatus = ConversationStatus::where('slug', 'resolved')->first();

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'status_id' => $openStatus->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withActions([
                ['action' => 'change_status', 'value' => 'resolved'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertEquals($resolvedStatus->id, $conversation->status_id);
    }

    public function test_action_change_priority(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $highPriority = ConversationPriority::where('slug', 'high')->first();
        $this->assertEquals($highPriority->id, $conversation->priority_id);
    }

    public function test_action_add_label_to_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => null,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withActions([
                ['action' => 'add_label', 'value' => 'urgent'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertEquals('urgent', $conversation->cached_label_list);
    }

    public function test_action_add_label_to_existing_labels(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => 'existing',
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withActions([
                ['action' => 'add_label', 'value' => 'urgent'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertStringContainsString('existing', $conversation->cached_label_list);
        $this->assertStringContainsString('urgent', $conversation->cached_label_list);
    }

    public function test_action_add_label_does_not_duplicate(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'cached_label_list' => 'urgent',
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withActions([
                ['action' => 'add_label', 'value' => 'urgent'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertEquals('urgent', $conversation->cached_label_list);
    }

    public function test_action_send_email_to_team(): void
    {
        Notification::fake();

        $team = Team::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $team->members()->attach($member1->id);
        $team->members()->attach($member2->id);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withActions([
                [
                    'action' => 'send_email_to_team',
                    'value' => [
                        'team_id' => $team->id,
                        'subject' => 'New Conversation',
                        'message' => 'A new conversation has been created',
                    ],
                ],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        Notification::assertSentTo($member1, AutomationTeamEmailNotification::class);
        Notification::assertSentTo($member2, AutomationTeamEmailNotification::class);
    }

    public function test_action_send_message_creates_message(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withActions([
                ['action' => 'send_message', 'value' => 'Thank you for contacting us!'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $this->assertDatabaseHas('chat_conversation_messages', [
            'conversation_id' => $conversation->id,
            'content' => 'Thank you for contacting us!',
            'message_type' => 'outgoing',
        ]);
    }

    public function test_handles_null_customer(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'customer_id' => null,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'contact_email', 'operator' => 'contains', 'value' => 'test'],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertNull($conversation->priority_id);
    }

    public function test_handles_invalid_condition_operator(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'status', 'operator' => 'invalid_operator', 'value' => 'open'],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertNull($conversation->priority_id);
    }

    public function test_handles_missing_condition_attribute(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['operator' => 'equals', 'value' => 'test'],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $this->assertNull($conversation->priority_id);
    }

    public function test_handles_custom_attributes(): void
    {
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'custom_attributes' => ['vip_status' => 'gold'],
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'vip_status', 'operator' => 'equals', 'value' => 'gold'],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $conversation->refresh();
        $highPriority = ConversationPriority::where('slug', 'high')->first();
        $this->assertEquals($highPriority->id, $conversation->priority_id);
    }

    public function test_eager_loads_relationships(): void
    {
        $customer = Customer::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'customer_id' => $customer->id,
        ]);

        $automation = Automation::factory()
            ->forEvent('conversation_created')
            ->withConditions([
                ['attribute' => 'contact_name', 'operator' => 'equals', 'value' => $customer->name],
            ])
            ->withActions([
                ['action' => 'change_priority', 'value' => 'high'],
            ])
            ->create([
                'account_id' => $this->account->id,
            ]);

        $this->executor->execute('conversation_created', $conversation);

        $this->assertTrue($conversation->relationLoaded('customer'));
        $this->assertTrue($conversation->relationLoaded('status'));
        $this->assertTrue($conversation->relationLoaded('priority'));
    }
}

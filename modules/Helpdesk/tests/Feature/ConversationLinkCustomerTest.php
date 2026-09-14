<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class ConversationLinkCustomerTest extends HelpdeskTestCase
{
    public function test_manager_can_link_conversation_to_another_customer(): void
    {
        $customer = Customer::factory()->create();
        $newCustomer = Customer::factory()->create();
        $conversation = $this->createConversation(['customer_id' => $customer->id]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.link-customer', $conversation), [
                'customer_id' => $newCustomer->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.id', $newCustomer->id);

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'customer_id' => $newCustomer->id,
        ], 'helpdesk');
    }

    public function test_linking_to_the_same_customer_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $conversation = $this->createConversation(['customer_id' => $customer->id]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.link-customer', $conversation), [
                'customer_id' => $customer->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('helpdesk::helpdesk.messages.link_customer_same'));

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'customer_id' => $customer->id,
        ], 'helpdesk');
    }

    public function test_user_without_permission_cannot_link_customer(): void
    {
        $customer = Customer::factory()->create();
        $newCustomer = Customer::factory()->create();
        $conversation = $this->createConversation(['customer_id' => $customer->id]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.link-customer', $conversation), [
                'customer_id' => $newCustomer->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'customer_id' => $customer->id,
        ], 'helpdesk');
    }

    public function test_guest_cannot_link_customer(): void
    {
        $customer = Customer::factory()->create();
        $newCustomer = Customer::factory()->create();
        $conversation = $this->createConversation(['customer_id' => $customer->id]);

        $this->postJson(route('manager.helpdesk.conversations.link-customer', $conversation), [
            'customer_id' => $newCustomer->id,
        ])->assertUnauthorized();
    }

    public function test_nonexistent_customer_id_fails_validation(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.link-customer', $conversation), [
                'customer_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('customer_id');
    }

    public function test_agent_without_inbox_access_cannot_link_customer(): void
    {
        $inboxWithAccess = $this->inbox('Inbox A');
        $inboxOfConversation = $this->inbox('Inbox B');

        $customer = Customer::factory()->create();
        $newCustomer = Customer::factory()->create();
        $conversation = $this->createConversation([
            'customer_id' => $customer->id,
            'inbox_id' => $inboxOfConversation->id,
        ]);

        $agent = User::factory()->create();
        $agent->givePermissionTo([
            'helpdesk.view',
            'helpdesk.conversations.view',
            'helpdesk.conversations.update',
            'helpdesk.conversations.link-customer',
        ]);
        AgentInboxCapacity::create([
            'user_id' => $agent->id,
            'inbox_id' => $inboxWithAccess->id,
            'max_concurrent' => 5,
            'accepts_new' => true,
        ]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversations.link-customer', $conversation), [
                'customer_id' => $newCustomer->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'customer_id' => $customer->id,
        ], 'helpdesk');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createConversation(array $overrides = []): Conversation
    {
        $conversation = Conversation::factory()->create($overrides);
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        return $conversation;
    }

    private function inbox(string $name): Inbox
    {
        return Inbox::create(['name' => $name, 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
    }
}

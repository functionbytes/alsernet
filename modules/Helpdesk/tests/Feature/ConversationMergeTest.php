<?php

namespace Modules\Helpdesk\Tests\Feature;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class ConversationMergeTest extends HelpdeskTestCase
{
    public function test_merge_updates_target_last_message_at_and_moves_items(): void
    {
        $customer = Customer::factory()->create();
        $source = $this->createConversation(['customer_id' => $customer->id, 'channel' => 'web', 'last_message_at' => now()]);
        $target = $this->createConversation(['customer_id' => $customer->id, 'channel' => 'web', 'last_message_at' => now()->subDays(3)]);
        $item = ConversationItem::factory()->fromCustomer($customer->id)->create(['conversation_id' => $source->id]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.merge', $source), ['target_id' => $target->id])
            ->assertOk()
            ->assertJsonPath('target_id', $target->id);

        $this->assertSame($target->id, $item->fresh()->conversation_id);
        $this->assertTrue($target->fresh()->last_message_at->gte($source->last_message_at->copy()->subSecond()));
        $this->assertSoftDeleted('helpdesk_conversations', ['id' => $source->id], 'helpdesk');
    }

    public function test_merge_across_different_channels_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $source = $this->createConversation(['customer_id' => $customer->id, 'channel' => 'web']);
        $target = $this->createConversation(['customer_id' => $customer->id, 'channel' => 'email']);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.merge', $source), ['target_id' => $target->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('helpdesk::helpdesk.messages.merge_different_channel'));

        $this->assertDatabaseHas('helpdesk_conversations', ['id' => $source->id, 'deleted_at' => null], 'helpdesk');
    }

    public function test_merge_validates_target_id(): void
    {
        $source = $this->createConversation(['channel' => 'web']);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.merge', $source), ['target_id' => $source->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target_id');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.merge', $source), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target_id');
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
}

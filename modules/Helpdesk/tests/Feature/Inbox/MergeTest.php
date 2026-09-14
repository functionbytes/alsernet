<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use App\Models\User;
use Modules\Helpdesk\Models\ConversationItem;

class MergeTest extends InboxTestCase
{
    public function test_merge_copies_items_to_target_and_soft_deletes_source(): void
    {
        $customer = $this->createCustomer();
        $source = $this->createConversation(['customer_id' => $customer->id]);
        $target = $this->createConversation(['customer_id' => $customer->id]);

        ConversationItem::factory()->count(2)->create([
            'conversation_id' => $source->id,
        ]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.merge', $source), [
                'target_id' => $target->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'target_id' => $target->id]);

        $this->assertSoftDeleted($source);

        $this->assertEquals(
            2,
            ConversationItem::where('conversation_id', $target->id)->count()
        );
    }

    public function test_merge_rejects_same_conversation(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.merge', $conversation), [
                'target_id' => $conversation->id,
            ])
            ->assertUnprocessable()
            ->assertJson(['success' => false]);
    }

    public function test_merge_rejects_conversations_from_different_customers(): void
    {
        $source = $this->createConversation();
        $target = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.merge', $source), [
                'target_id' => $target->id,
            ])
            ->assertUnprocessable()
            ->assertJson(['success' => false]);
    }

    public function test_merge_candidates_returns_same_customer_conversations(): void
    {
        $customer = $this->createCustomer();
        $conversation = $this->createConversation(['customer_id' => $customer->id]);

        $other = $this->createConversation(['customer_id' => $customer->id]);
        $unrelated = $this->createConversation();

        $response = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.merge-candidates', $conversation));

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($other->id));
        $this->assertFalse($ids->contains($conversation->id));
        $this->assertFalse($ids->contains($unrelated->id));
    }

    public function test_merge_requires_auth(): void
    {
        $conversation = $this->createConversation();

        $this->post(route('manager.helpdesk.conversations.merge', $conversation), [
            'target_id' => 999,
        ])->assertRedirect();
    }

    public function test_user_without_permission_cannot_merge(): void
    {
        $customer = $this->createCustomer();
        $source = $this->createConversation(['customer_id' => $customer->id]);
        $target = $this->createConversation(['customer_id' => $customer->id]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.merge', $source), [
                'target_id' => $target->id,
            ])
            ->assertForbidden();
    }
}

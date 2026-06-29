<?php

namespace Modules\Helpdesk\Tests\Feature;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class BulkConversationsTest extends HelpdeskTestCase
{
    public function test_bulk_close_conversations(): void
    {
        $conversationA = $this->createConversation();
        $conversationB = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'ids' => [$conversationA->id, $conversationB->id],
                'action' => 'close',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull(Conversation::find($conversationA->id)->closed_at);
        $this->assertNotNull(Conversation::find($conversationB->id)->closed_at);
    }

    public function test_bulk_archive_conversations(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'ids' => [$conversation->id],
                'action' => 'archive',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue((bool) Conversation::find($conversation->id)->is_archived);
    }

    public function test_bulk_action_requires_auth(): void
    {
        $conversation = $this->createConversation();

        $this->postJson(route('manager.helpdesk.conversations.bulk'), [
            'ids' => [$conversation->id],
            'action' => 'close',
        ])->assertUnauthorized();
    }

    public function test_validation_fails_with_empty_ids(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'ids' => [],
                'action' => 'close',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createConversation(array $overrides = []): Conversation
    {
        $conversation = Conversation::create(array_merge([
            'customer_id' => Customer::factory()->create()->id,
            'subject' => 'Test conversation',
            'priority' => 'normal',
            'channel' => 'web',
        ], $overrides));
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        return $conversation;
    }
}

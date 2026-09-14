<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Group;
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

    public function test_bulk_priority_updates_conversations(): void
    {
        $conversation = $this->createConversation(['priority' => 'normal']);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'ids' => [$conversation->id],
                'action' => 'priority',
                'payload' => ['priority' => 'urgent'],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('urgent', Conversation::find($conversation->id)->priority);
    }

    public function test_bulk_team_moves_conversations_to_group(): void
    {
        $group = Group::create(['name' => 'Facturación']);
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'ids' => [$conversation->id],
                'action' => 'team',
                'payload' => ['group_id' => $group->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame($group->id, Conversation::find($conversation->id)->group_id);
    }

    public function test_bulk_snooze_sets_snoozed_until(): void
    {
        $conversation = $this->createConversation();
        $until = now()->addDay()->toIso8601String();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'ids' => [$conversation->id],
                'action' => 'snooze',
                'payload' => ['until' => $until],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull(Conversation::find($conversation->id)->snoozed_until);
    }

    public function test_bulk_mute_is_per_agent_not_global(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'ids' => [$conversation->id],
                'action' => 'mute',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $meta = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('user_id', $this->manager->id)
            ->where('conversation_id', $conversation->id)
            ->first();

        $this->assertNotNull($meta);
        $this->assertNotNull($meta->muted_until);
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

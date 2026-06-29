<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use App\Models\User;
use Modules\Helpdesk\Models\Conversation;

class BulkActionsTest extends InboxTestCase
{
    public function test_bulk_archive_archives_multiple_conversations(): void
    {
        $conversations = collect(range(1, 5))->map(fn () => $this->createConversation());

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'action' => 'archive',
                'ids' => $conversations->pluck('id')->toArray(),
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        foreach ($conversations as $conversation) {
            $this->assertTrue(
                (bool) Conversation::find($conversation->id)->is_archived,
                "Conversation {$conversation->id} was not archived"
            );
        }
    }

    public function test_bulk_assign_assigns_to_user(): void
    {
        $agent = User::factory()->create();
        $conversations = collect(range(1, 3))->map(fn () => $this->createConversation());

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'action' => 'assign',
                'ids' => $conversations->pluck('id')->toArray(),
                'payload' => ['assignee_id' => $agent->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        foreach ($conversations as $conversation) {
            $this->assertEquals(
                $agent->id,
                Conversation::find($conversation->id)->assignee_id
            );
        }
    }

    public function test_bulk_close_closes_multiple_conversations(): void
    {
        $conversations = collect(range(1, 3))->map(fn () => $this->createConversation());

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'action' => 'close',
                'ids' => $conversations->pluck('id')->toArray(),
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        foreach ($conversations as $conversation) {
            $this->assertNotNull(
                Conversation::find($conversation->id)->closed_at
            );
        }
    }

    public function test_bulk_validation_max_100_ids(): void
    {
        $ids = range(1, 101);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'action' => 'archive',
                'ids' => $ids,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids']);
    }

    public function test_bulk_validation_requires_ids(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'action' => 'close',
                'ids' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids']);
    }

    public function test_bulk_validation_requires_valid_action(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'action' => 'invalid_action',
                'ids' => [$conversation->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    }

    public function test_bulk_requires_auth(): void
    {
        $conversation = $this->createConversation();

        $this->post(route('manager.helpdesk.conversations.bulk'), [
            'action' => 'close',
            'ids' => [$conversation->id],
        ])->assertRedirect();
    }

    public function test_user_without_permission_cannot_perform_bulk_action(): void
    {
        $conversation = $this->createConversation();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'action' => 'close',
                'ids' => [$conversation->id],
            ])
            ->assertForbidden();
    }
}

<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use App\Models\User;
use Modules\Helpdesk\Models\Conversation;

class CloseReopenArchiveTest extends InboxTestCase
{
    public function test_close_marks_conversation_closed(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.close', $conversation))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull(
            Conversation::find($conversation->id)->closed_at
        );
    }

    public function test_close_requires_auth(): void
    {
        $conversation = $this->createConversation();

        $this->post(route('manager.helpdesk.conversations.close', $conversation))
            ->assertRedirect();
    }

    public function test_reopen_clears_closed_at(): void
    {
        $conversation = $this->createConversation(['closed_at' => now()]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.reopen', $conversation))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(
            Conversation::find($conversation->id)->closed_at
        );
    }

    public function test_archive_sets_is_archived_true(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.archive', $conversation))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue(
            (bool) Conversation::find($conversation->id)->is_archived
        );
    }

    public function test_unarchive_clears_is_archived(): void
    {
        $conversation = $this->createConversation(['is_archived' => true]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.unarchive', $conversation))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertFalse(
            (bool) Conversation::find($conversation->id)->is_archived
        );
    }

    public function test_user_without_permission_cannot_close_conversation(): void
    {
        $conversation = $this->createConversation();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.close', $conversation))
            ->assertForbidden();
    }
}

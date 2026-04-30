<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Events\ConversationItemCreated;

class StoreMessageTest extends InboxTestCase
{
    public function test_authenticated_user_can_send_message_via_ajax(): void
    {
        $conversation = $this->createConversation();

        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.messages.store', $conversation), [
                'body' => 'Hello, how can I help you?',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'item' => ['id', 'body', 'is_internal', 'created_at'],
            ]);

        $this->assertDatabaseHas('helpdesk_conversation_items', [
            'conversation_id' => $conversation->id,
            'body' => 'Hello, how can I help you?',
            'type' => 'message',
            'is_internal' => 0,
        ]);
    }

    public function test_message_validation_fails_with_empty_body(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.messages.store', $conversation), [
                'body' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    }

    public function test_unauthenticated_user_gets_redirected(): void
    {
        $conversation = $this->createConversation();

        $this->post(route('manager.helpdesk.conversations.messages.store', $conversation), [
            'body' => 'Test message',
        ])->assertRedirect();
    }

    public function test_message_with_attachments_persists_attachment_urls(): void
    {
        Storage::fake('public');

        $conversation = $this->createConversation();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.messages.store', $conversation), [
                'body' => 'See attached file',
                'attachments' => [$file],
            ])
            ->assertCreated();

        $item = $conversation->items()->latest()->first();
        $this->assertNotNull($item);
        $this->assertNotEmpty($item->attachment_urls);
    }

    public function test_internal_note_message_marked_is_internal(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.messages.store', $conversation), [
                'body' => 'Internal note for agents only',
                'is_internal' => 1,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('helpdesk_conversation_items', [
            'conversation_id' => $conversation->id,
            'body' => 'Internal note for agents only',
            'is_internal' => 1,
        ]);
    }

    public function test_store_message_dispatches_conversation_item_created_event(): void
    {
        Event::fake([ConversationItemCreated::class]);

        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.messages.store', $conversation), [
                'body' => 'Event test message',
            ])
            ->assertCreated();

        Event::assertDispatched(ConversationItemCreated::class);
    }
}

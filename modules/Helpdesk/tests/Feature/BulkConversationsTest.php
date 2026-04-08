<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Tests\TestCase;

class BulkConversationsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private ConversationStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->manager = User::factory()->create();

        $this->openStatus = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );
    }

    public function test_bulk_close_conversations(): void
    {
        $conversationA = $this->createConversation();
        $conversationB = $this->createConversation();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.bulk'), [
                'conversation_ids' => [$conversationA->id, $conversationB->id],
                'action' => 'close',
            ])
            ->assertRedirect(route('manager.helpdesk.conversations.index'));

        $this->assertNotNull(Conversation::find($conversationA->id)->closed_at);
        $this->assertNotNull(Conversation::find($conversationB->id)->closed_at);
    }

    public function test_bulk_archive_conversations(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.bulk'), [
                'conversation_ids' => [$conversation->id],
                'action' => 'archive',
            ])
            ->assertRedirect(route('manager.helpdesk.conversations.index'));

        $this->assertTrue((bool) Conversation::find($conversation->id)->is_archived);
    }

    public function test_bulk_delete_conversations(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.bulk'), [
                'conversation_ids' => [$conversation->id],
                'action' => 'delete',
            ])
            ->assertRedirect(route('manager.helpdesk.conversations.index'));

        $this->assertNull(Conversation::find($conversation->id));
    }

    public function test_bulk_action_requires_auth(): void
    {
        $conversation = $this->createConversation();

        $this->post(route('manager.helpdesk.conversations.bulk'), [
            'conversation_ids' => [$conversation->id],
            'action' => 'close',
        ])->assertRedirect('/login');
    }

    public function test_validation_fails_with_empty_ids(): void
    {
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.bulk'), [
                'conversation_ids' => [],
                'action' => 'close',
            ])
            ->assertSessionHasErrors(['conversation_ids']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createConversation(array $overrides = []): Conversation
    {
        return Conversation::create(array_merge([
            'subject' => 'Test conversation',
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'channel' => 'web',
        ], $overrides));
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

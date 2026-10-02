<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationRead;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * El leído del inbox es compartido: cuando un agente abre la conversación deja
 * de contar como "sin leer" para el resto del equipo, hasta que llega otro
 * mensaje.
 */
class SharedConversationReadTest extends HelpdeskTestCase
{
    private User $otherAgent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->otherAgent = User::factory()->create();
        $this->otherAgent->assignRole('super-settings');
    }

    public function test_reading_by_one_agent_clears_unread_for_the_rest(): void
    {
        Event::fake([ConversationUpdated::class]);
        $conversation = $this->unreadConversation();

        $this->assertTrue($this->isUnread($conversation));

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.mark-read', $conversation))
            ->assertOk();

        $this->assertFalse($this->isUnread($conversation));

        $this->actingAs($this->otherAgent);
        $card = Conversation::query()
            ->withMax(['reads as agent_last_read_at' => fn ($q) => $q->where('user_id', '>', 0)], 'read_at')
            ->findOrFail($conversation->id)
            ->toInboxArray();
        $this->assertSame(0, $card['unread']);

        Event::assertDispatched(ConversationUpdated::class, fn (ConversationUpdated $e) => $e->read
            && $e->conversation->is($conversation)
            && $e->byUserId === $this->manager->id);
    }

    public function test_reopening_an_already_read_conversation_does_not_broadcast_again(): void
    {
        $conversation = $this->unreadConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.mark-read', $conversation))
            ->assertOk();

        Event::fake([ConversationUpdated::class]);

        $this->actingAs($this->otherAgent)
            ->postJson(route('manager.helpdesk.conversations.mark-read', $conversation))
            ->assertOk();

        Event::assertNotDispatched(ConversationUpdated::class);
    }

    public function test_a_new_message_after_the_read_makes_it_unread_again(): void
    {
        $conversation = $this->unreadConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.mark-read', $conversation))
            ->assertOk();

        $this->travel(5)->minutes();
        $conversation->forceFill(['last_message_at' => now()])->save();

        $this->assertTrue($this->isUnread($conversation));
    }

    public function test_the_widget_visitor_read_does_not_count_as_an_agent_read(): void
    {
        $conversation = $this->unreadConversation();

        ConversationRead::create([
            'conversation_id' => $conversation->id,
            'user_id' => 0,
            'read_at' => now()->addMinute(),
        ]);

        $this->assertTrue($this->isUnread($conversation));
    }

    public function test_bulk_mark_unread_makes_it_unread_for_everyone_and_keeps_the_visitor_read(): void
    {
        $conversation = $this->unreadConversation();

        ConversationRead::create(['conversation_id' => $conversation->id, 'user_id' => 0, 'read_at' => now()]);
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.mark-read', $conversation))
            ->assertOk();
        $this->assertFalse($this->isUnread($conversation));

        $this->actingAs($this->otherAgent)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'action' => 'mark_unread',
                'ids' => [$conversation->id],
            ])
            ->assertOk()
            ->assertJsonPath('affected', 1);

        $this->assertTrue($this->isUnread($conversation));
        $this->assertDatabaseHas('helpdesk_conversation_reads', ['conversation_id' => $conversation->id, 'user_id' => 0], 'helpdesk');
    }

    private function unreadConversation(): Conversation
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'web',
            'is_archived' => false,
            'last_message_at' => now()->subMinute(),
        ]);
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        ConversationItem::factory()->fromCustomer($customer->id)->create(['conversation_id' => $conversation->id]);
        $conversation->forceFill(['last_message_at' => now()->subMinute()])->save();

        return $conversation;
    }

    private function isUnread(Conversation $conversation): bool
    {
        return Conversation::query()->whereKey($conversation->id)->unreadFor()->exists();
    }
}

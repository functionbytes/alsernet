<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Events\WidgetTyping;
use Tests\TestCase;

/**
 * Regression tests for ownership enforcement on typing and read endpoints.
 *
 * Both POST /hd/api/conversation/{id}/typing and /read must return 403 when
 * the supplied customer_id does not own the conversation, and must not
 * dispatch events or persist state on such attempts.
 */
class ConversationOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private Customer $owner;

    private Customer $attacker;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->owner = Customer::factory()->create();
        $this->attacker = Customer::factory()->create();

        $this->conversation = Conversation::factory()->create([
            'customer_id' => $this->owner->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // typing — failure paths
    // -----------------------------------------------------------------------

    public function test_typing_with_wrong_customer_id_returns_403(): void
    {
        Event::fake([WidgetTyping::class]);

        $this->postJson(
            route('helpdesk-livechat.widget.conversation.typing', $this->conversation->id),
            ['customer_id' => $this->attacker->id]
        )->assertForbidden()
            ->assertJsonPath('error', 'Forbidden');
    }

    public function test_typing_with_wrong_customer_id_does_not_dispatch_event(): void
    {
        Event::fake([WidgetTyping::class]);

        $this->postJson(
            route('helpdesk-livechat.widget.conversation.typing', $this->conversation->id),
            ['customer_id' => $this->attacker->id]
        );

        Event::assertNotDispatched(WidgetTyping::class);
    }

    public function test_typing_without_identity_returns_401(): void
    {
        $this->postJson(
            route('helpdesk-livechat.widget.conversation.typing', $this->conversation->id),
            []
        )->assertUnauthorized();
    }

    // -----------------------------------------------------------------------
    // typing — happy path
    // -----------------------------------------------------------------------

    public function test_typing_with_correct_customer_id_returns_200_and_dispatches_event(): void
    {
        Event::fake([WidgetTyping::class]);

        $this->postJson(
            route('helpdesk-livechat.widget.conversation.typing', $this->conversation->id),
            ['customer_id' => $this->owner->id]
        )->assertOk()
            ->assertJsonPath('success', true);

        Event::assertDispatched(
            WidgetTyping::class,
            fn (WidgetTyping $e) => $e->conversationId === $this->conversation->id
        );
    }

    // -----------------------------------------------------------------------
    // read — failure paths
    // -----------------------------------------------------------------------

    public function test_read_with_wrong_customer_id_returns_403(): void
    {
        $this->postJson(
            route('helpdesk-livechat.widget.conversation.read', $this->conversation->id),
            ['customer_id' => $this->attacker->id]
        )->assertForbidden()
            ->assertJsonPath('error', 'Forbidden');
    }

    public function test_read_with_wrong_customer_id_does_not_create_conversation_read(): void
    {
        $this->postJson(
            route('helpdesk-livechat.widget.conversation.read', $this->conversation->id),
            ['customer_id' => $this->attacker->id]
        );

        $this->assertDatabaseMissing('helpdesk_conversation_reads', [
            'conversation_id' => $this->conversation->id,
            'user_id' => 0,
        ]);
    }

    public function test_read_without_identity_returns_401(): void
    {
        $this->postJson(
            route('helpdesk-livechat.widget.conversation.read', $this->conversation->id),
            []
        )->assertUnauthorized();
    }

    // -----------------------------------------------------------------------
    // read — happy path
    // -----------------------------------------------------------------------

    public function test_read_with_correct_customer_id_creates_conversation_read(): void
    {
        $this->postJson(
            route('helpdesk-livechat.widget.conversation.read', $this->conversation->id),
            ['customer_id' => $this->owner->id]
        )->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_conversation_reads', [
            'conversation_id' => $this->conversation->id,
            'user_id' => 0,
        ]);
    }

    public function test_read_with_correct_customer_email_creates_conversation_read(): void
    {
        $this->postJson(
            route('helpdesk-livechat.widget.conversation.read', $this->conversation->id),
            ['customer_email' => $this->owner->email]
        )->assertOk();

        $this->assertDatabaseHas('helpdesk_conversation_reads', [
            'conversation_id' => $this->conversation->id,
        ]);
    }
}

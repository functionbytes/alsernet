<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Events\WidgetTyping;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * Regression tests for per-conversation authorization on the typing and read
 * endpoints.
 *
 * Authorization is based on the conversation's secret `widget_pubsub_token`
 * (sent via the X-Conversation-Token header), NOT on a client-supplied
 * customer_id. A request must be rejected (401) when the token is missing or
 * wrong — even if it carries the real owner's (guessable, sequential)
 * customer_id. This is the IDOR that the token model closes.
 */
class ConversationOwnershipTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $owner;

    private Customer $attacker;

    private Conversation $conversation;

    private string $token = 'pubsub_owner_secret_token_value';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedOpenConversationStatus();

        $this->owner = Customer::factory()->create();
        $this->attacker = Customer::factory()->create();

        $this->conversation = Conversation::factory()->create([
            'customer_id' => $this->owner->id,
            'metadata' => ['widget_pubsub_token' => $this->token],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function tokenHeader(string $token): array
    {
        return ['X-Conversation-Token' => $token];
    }

    // -----------------------------------------------------------------------
    // typing — failure paths
    // -----------------------------------------------------------------------

    public function test_typing_without_token_returns_401(): void
    {
        Event::fake([WidgetTyping::class]);

        $this->postJson(
            route('helpdesk-livechat.widget.conversation.typing', $this->conversation->id),
            []
        )->assertUnauthorized();

        Event::assertNotDispatched(WidgetTyping::class);
    }

    public function test_typing_with_wrong_token_returns_401_even_with_owner_customer_id(): void
    {
        Event::fake([WidgetTyping::class]);

        $this->withHeaders($this->tokenHeader('not-the-real-token'))->postJson(
            route('helpdesk-livechat.widget.conversation.typing', $this->conversation->id),
            ['customer_id' => $this->owner->id]
        )->assertUnauthorized();

        Event::assertNotDispatched(WidgetTyping::class);
    }

    // -----------------------------------------------------------------------
    // typing — happy path
    // -----------------------------------------------------------------------

    public function test_typing_with_correct_token_returns_200_and_dispatches_event(): void
    {
        Event::fake([WidgetTyping::class]);

        $this->withHeaders($this->tokenHeader($this->token))->postJson(
            route('helpdesk-livechat.widget.conversation.typing', $this->conversation->id),
            []
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

    public function test_read_without_token_returns_401(): void
    {
        $this->postJson(
            route('helpdesk-livechat.widget.conversation.read', $this->conversation->id),
            []
        )->assertUnauthorized();

        $this->assertDatabaseMissing('helpdesk_conversation_reads', [
            'conversation_id' => $this->conversation->id,
            'user_id' => 0,
        ], 'helpdesk');
    }

    public function test_read_with_wrong_token_returns_401_even_with_owner_customer_id(): void
    {
        $this->withHeaders($this->tokenHeader('not-the-real-token'))->postJson(
            route('helpdesk-livechat.widget.conversation.read', $this->conversation->id),
            ['customer_id' => $this->owner->id]
        )->assertUnauthorized();

        $this->assertDatabaseMissing('helpdesk_conversation_reads', [
            'conversation_id' => $this->conversation->id,
            'user_id' => 0,
        ], 'helpdesk');
    }

    // -----------------------------------------------------------------------
    // read — happy path
    // -----------------------------------------------------------------------

    public function test_read_with_correct_token_creates_conversation_read(): void
    {
        $this->withHeaders($this->tokenHeader($this->token))->postJson(
            route('helpdesk-livechat.widget.conversation.read', $this->conversation->id),
            []
        )->assertOk()
            ->assertJsonPath('success', true);

        // Conexión 'helpdesk': ConversationRead escribe por esa conexión y, con
        // ambas transaccionadas, la conexión por defecto no ve la fila sin commitear.
        $this->assertDatabaseHas('helpdesk_conversation_reads', [
            'conversation_id' => $this->conversation->id,
            'user_id' => 0,
        ], 'helpdesk');
    }
}

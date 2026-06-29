<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Tests\TestCase;

/**
 * Read access to a conversation (show + messages) is authorized by the
 * per-conversation secret token (X-Conversation-Token), never by a
 * client-supplied customer_id/email. Guessing the owner's sequential
 * customer_id must NOT grant access (IDOR closed).
 */
class WidgetConversationSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private Conversation $conversation;

    private string $token = 'pubsub_show_secret_token_value';

    protected function setUp(): void
    {
        parent::setUp();

        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::factory()->create();
        $this->conversation = Conversation::factory()->create([
            'customer_id' => $this->customer->id,
            'metadata' => ['widget_pubsub_token' => $this->token],
        ]);
        $this->conversation->status_id = $status->id;
        $this->conversation->save();
    }

    /**
     * @return array<string, string>
     */
    private function tokenHeader(string $token): array
    {
        return ['X-Conversation-Token' => $token];
    }

    public function test_show_requires_token(): void
    {
        $this->getJson(route('helpdesk-livechat.widget.conversation.show', $this->conversation->id))
            ->assertUnauthorized()
            ->assertJsonPath('error', 'Unauthorized');
    }

    public function test_show_rejects_guessed_customer_id_without_token(): void
    {
        $this->getJson(route('helpdesk-livechat.widget.conversation.show', $this->conversation->id).'?customer_id='.$this->customer->id)
            ->assertUnauthorized();
    }

    public function test_show_allows_owner_with_token(): void
    {
        $this->withHeaders($this->tokenHeader($this->token))
            ->getJson(route('helpdesk-livechat.widget.conversation.show', $this->conversation->id))
            ->assertOk()
            ->assertJsonPath('data.id', $this->conversation->id);
    }

    public function test_get_messages_requires_token(): void
    {
        $this->getJson(route('helpdesk-livechat.widget.conversation.messages.index', $this->conversation->id))
            ->assertUnauthorized()
            ->assertJsonPath('error', 'Unauthorized');
    }

    public function test_get_messages_rejects_wrong_token(): void
    {
        $this->withHeaders($this->tokenHeader('wrong-token'))
            ->getJson(route('helpdesk-livechat.widget.conversation.messages.index', $this->conversation->id))
            ->assertUnauthorized();
    }

    public function test_get_messages_allows_owner_with_token(): void
    {
        $this->withHeaders($this->tokenHeader($this->token))
            ->getJson(route('helpdesk-livechat.widget.conversation.messages.index', $this->conversation->id))
            ->assertOk()
            ->assertJsonPath('data.messages', []);
    }
}

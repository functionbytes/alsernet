<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
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
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private Conversation $conversation;

    private string $token = 'pubsub_show_secret_token_value';

    protected function setUp(): void
    {
        parent::setUp();

        $status = $this->seedOpenConversationStatus();

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

    /**
     * Regresión: el customer_id que envía el cliente al crear conversación NO
     * debe usarse — antes permitía adjuntar el chat a cualquier cliente y
     * sobrescribir su email/nombre. La identidad se resuelve server-side.
     */
    public function test_store_ignores_client_supplied_customer_id(): void
    {
        Event::fake([ConversationCreated::class, ConversationMessageCreated::class, MessageReceived::class]);

        $victim = Customer::factory()->create(['email' => 'victima@example.com', 'name' => 'Victima']);
        $web = WebFactory::new()->create();

        $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $web->website_token,
            'customer_id' => $victim->id,        // intento de impersonación
            'email' => 'atacante@example.com',   // intento de sobrescribir el email de la víctima
            'name' => 'Atacante',
            'message' => 'hola',
        ])->assertOk();

        // El email/nombre de la víctima no deben cambiar.
        $victim->refresh();
        $this->assertSame('victima@example.com', $victim->email, 'El email de la víctima no debe sobrescribirse.');
        $this->assertSame('Victima', $victim->name);

        // La conversación creada no debe pertenecer a la víctima.
        $this->assertFalse(
            Conversation::where('customer_id', $victim->id)->exists(),
            'La conversación no debe adjuntarse al customer_id enviado por el cliente.'
        );
    }
}

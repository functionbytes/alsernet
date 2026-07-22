<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * Regresión IDOR en PreChatFormApiController::submit.
 *
 * La propiedad de la conversación se verifica con el token del widget
 * (X-Conversation-Token contra metadata.widget_pubsub_token, hash_equals), NO con
 * customer_id/customer_email — que son secuenciales/conocibles y permitían a un
 * visitante sobrescribir el pre-chat y el email de conversaciones ajenas.
 */
class PreChatFormOwnershipTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $owner;

    private Conversation $conversation;

    private string $token = 'valid-widget-token-abc123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedOpenConversationStatus();

        $this->owner = Customer::factory()->create(['email' => 'owner@example.com']);

        $this->conversation = Conversation::factory()->create([
            'customer_id' => $this->owner->id,
            'metadata' => ['widget_pubsub_token' => $this->token],
        ]);
    }

    // ── Failure paths: IDOR / token inválido ────────────────────────────────

    public function test_submit_without_token_returns_403(): void
    {
        $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'data' => ['name' => 'Attacker', 'email' => 'attacker@example.com'],
        ])
            ->assertForbidden()
            ->assertJsonPath('error', 'Forbidden');
    }

    public function test_submit_with_wrong_token_returns_403(): void
    {
        $this->withHeader('X-Conversation-Token', 'not-the-real-token')
            ->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
                'conversation_id' => $this->conversation->id,
                'data' => ['name' => 'Attacker'],
            ])
            ->assertForbidden()
            ->assertJsonPath('error', 'Forbidden');
    }

    public function test_supplying_customer_id_does_not_bypass_token(): void
    {
        // Aunque el atacante conozca el customer_id del dueño, sin el token no entra.
        $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'data' => ['name' => 'Attacker'],
            'customer_id' => $this->owner->id,
            'customer_email' => 'owner@example.com',
        ])
            ->assertForbidden()
            ->assertJsonPath('error', 'Forbidden');
    }

    public function test_idor_does_not_mutate_metadata_when_forbidden(): void
    {
        $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'data' => ['name' => 'Attacker', 'email' => 'attacker@example.com'],
        ])->assertForbidden();

        $this->conversation->refresh();
        $metadata = json_decode((string) $this->conversation->getRawOriginal('metadata'), true) ?: [];
        $this->assertArrayNotHasKey('pre_chat', $metadata);
    }

    public function test_idor_does_not_mutate_customer_when_forbidden(): void
    {
        $anonymous = Customer::factory()->create(['email' => 'visitor@anonymous.local']);
        $conversation = Conversation::factory()->create([
            'customer_id' => $anonymous->id,
            'metadata' => ['widget_pubsub_token' => 'a-different-token'],
        ]);

        $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $conversation->id,
            'data' => ['email' => 'injected@example.com'],
        ])->assertForbidden();

        $this->assertSame('visitor@anonymous.local', $anonymous->refresh()->email);
    }

    // ── Happy path: token correcto ──────────────────────────────────────────

    public function test_submit_with_valid_token_header_persists_metadata(): void
    {
        $this->withHeader('X-Conversation-Token', $this->token)
            ->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
                'conversation_id' => $this->conversation->id,
                'data' => ['name' => 'Owner Name', 'phone' => '+34600000000'],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->conversation->refresh();
        $metadata = json_decode((string) $this->conversation->getRawOriginal('metadata'), true);
        $this->assertSame('Owner Name', $metadata['pre_chat']['name'] ?? null);
    }

    public function test_submit_with_valid_token_via_body_field_persists_metadata(): void
    {
        // El trait acepta también el token por el campo conversation_token del body.
        $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'conversation_token' => $this->token,
            'data' => ['name' => 'Via Body'],
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->conversation->refresh();
        $metadata = json_decode((string) $this->conversation->getRawOriginal('metadata'), true);
        $this->assertSame('Via Body', $metadata['pre_chat']['name'] ?? null);
    }
}

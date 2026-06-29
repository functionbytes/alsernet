<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Tests\TestCase;

/**
 * Regression tests for IDOR in PreChatFormApiController::submit.
 *
 * Verifies that a requester cannot submit a pre-chat form on behalf of a
 * conversation they do not own, either by supplying a mismatched customer_id
 * or a mismatched customer_email.
 */
class PreChatFormOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

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

        $this->owner = Customer::factory()->create(['email' => 'owner@example.com']);
        $this->attacker = Customer::factory()->create(['email' => 'attacker@example.com']);

        $this->conversation = Conversation::factory()->create([
            'customer_id' => $this->owner->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // Failure paths: IDOR attempts
    // -----------------------------------------------------------------------

    public function test_submit_with_wrong_customer_id_returns_403(): void
    {
        $response = $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'data' => ['name' => 'Attacker', 'email' => 'attacker@example.com'],
            'customer_id' => $this->attacker->id,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('error', 'Forbidden');
    }

    public function test_submit_with_wrong_customer_email_returns_403(): void
    {
        $response = $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'data' => ['name' => 'Attacker'],
            'customer_email' => 'attacker@example.com',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('error', 'Forbidden');
    }

    public function test_submit_with_no_identity_returns_403(): void
    {
        $response = $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'data' => ['name' => 'Anonymous'],
            // no customer_id, no customer_email
        ]);

        $response->assertForbidden()
            ->assertJsonPath('error', 'Forbidden');
    }

    public function test_idor_does_not_mutate_metadata_when_forbidden(): void
    {
        $originalMetadata = $this->conversation->getRawOriginal('metadata');

        $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'data' => ['name' => 'Attacker', 'email' => 'attacker@example.com'],
            'customer_id' => $this->attacker->id,
        ]);

        $this->conversation->refresh();
        $this->assertSame($originalMetadata, $this->conversation->getRawOriginal('metadata'));
    }

    public function test_idor_does_not_mutate_customer_when_forbidden(): void
    {
        $originalEmail = $this->attacker->email;

        $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'data' => ['email' => 'injected@example.com'],
            'customer_id' => $this->attacker->id,
        ]);

        $this->attacker->refresh();
        $this->assertSame($originalEmail, $this->attacker->email);
    }

    // -----------------------------------------------------------------------
    // Happy path: owner submits successfully
    // -----------------------------------------------------------------------

    public function test_submit_with_correct_customer_id_persists_metadata(): void
    {
        $response = $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'data' => ['name' => 'Owner Name', 'phone' => '+34600000000'],
            'customer_id' => $this->owner->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->conversation->refresh();
        $metadata = json_decode((string) $this->conversation->getRawOriginal('metadata'), true);
        $this->assertSame('Owner Name', $metadata['pre_chat']['name'] ?? null);
    }

    public function test_submit_with_correct_customer_email_persists_metadata(): void
    {
        $response = $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'data' => ['name' => 'Via Email'],
            'customer_email' => 'owner@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->conversation->refresh();
        $metadata = json_decode((string) $this->conversation->getRawOriginal('metadata'), true);
        $this->assertSame('Via Email', $metadata['pre_chat']['name'] ?? null);
    }

    public function test_submit_email_matching_is_case_insensitive(): void
    {
        $response = $this->postJson(route('helpdesklivechat.pre-chat-form.submit'), [
            'conversation_id' => $this->conversation->id,
            'data' => ['name' => 'Case Test'],
            'customer_email' => 'OWNER@EXAMPLE.COM',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}

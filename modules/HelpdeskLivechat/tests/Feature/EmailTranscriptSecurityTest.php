<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Mail\ConversationTranscriptMail;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * Regression tests for email-transcript endpoint security.
 *
 * Covers:
 *  (a) enable_email_transcripts = false → 403, no mail queued
 *  (b) flag enabled but destination email ≠ customer email → 403
 *  (c) flag enabled and destination email matches customer → 200, mail queued
 */
class EmailTranscriptSecurityTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private Conversation $conversation;

    /** @var Web */
    private $web;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedOpenConversationStatus();

        $this->customer = Customer::factory()->create(['email' => 'customer@example.com']);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function buildConversationWithWeb(bool $enableTranscripts): void
    {
        $this->web = WebFactory::new()->create([
            'enable_email_transcripts' => $enableTranscripts,
        ]);

        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $this->web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Transcript Inbox', 'is_active' => true]
        );

        $this->conversation = Conversation::factory()->create([
            'customer_id' => $this->customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'metadata' => ['widget_pubsub_token' => 'pubsub_transcript_token'],
        ]);

        // These tests target the transcript-specific checks (enabled flag,
        // destination-email match), so authorize past the per-conversation
        // token gate. The header persists across requests in the test.
        $this->withHeader('X-Conversation-Token', 'pubsub_transcript_token');
    }

    // -----------------------------------------------------------------------
    // (a) Transcripts disabled
    // -----------------------------------------------------------------------

    public function test_transcript_disabled_returns_403(): void
    {
        Mail::fake();
        $this->buildConversationWithWeb(enableTranscripts: false);

        $this->postJson(
            route('helpdesk-livechat.widget.conversation.email-transcript', $this->conversation->id),
            [
                'email' => $this->customer->email,
                'customer_id' => $this->customer->id,
            ]
        )->assertForbidden()
            ->assertJsonPath('error', 'Email transcripts are not enabled');
    }

    public function test_transcript_disabled_does_not_queue_mail(): void
    {
        Mail::fake();
        $this->buildConversationWithWeb(enableTranscripts: false);

        $this->postJson(
            route('helpdesk-livechat.widget.conversation.email-transcript', $this->conversation->id),
            [
                'email' => $this->customer->email,
                'customer_id' => $this->customer->id,
            ]
        );

        Mail::assertNothingQueued();
    }

    // -----------------------------------------------------------------------
    // (b) Transcripts enabled but destination email mismatches
    // -----------------------------------------------------------------------

    public function test_transcript_enabled_but_wrong_destination_email_returns_403(): void
    {
        Mail::fake();
        $this->buildConversationWithWeb(enableTranscripts: true);

        $this->postJson(
            route('helpdesk-livechat.widget.conversation.email-transcript', $this->conversation->id),
            [
                'email' => 'attacker@example.com',
                'customer_id' => $this->customer->id,
            ]
        )->assertForbidden()
            ->assertJsonPath('error', 'Forbidden');
    }

    public function test_transcript_with_wrong_destination_does_not_queue_mail(): void
    {
        Mail::fake();
        $this->buildConversationWithWeb(enableTranscripts: true);

        $this->postJson(
            route('helpdesk-livechat.widget.conversation.email-transcript', $this->conversation->id),
            [
                'email' => 'attacker@example.com',
                'customer_id' => $this->customer->id,
            ]
        );

        Mail::assertNothingQueued();
    }

    // -----------------------------------------------------------------------
    // (c) Happy path: flag enabled, email matches customer
    // -----------------------------------------------------------------------

    public function test_transcript_enabled_with_correct_email_queues_mail(): void
    {
        Mail::fake();
        $this->buildConversationWithWeb(enableTranscripts: true);

        $this->postJson(
            route('helpdesk-livechat.widget.conversation.email-transcript', $this->conversation->id),
            [
                'email' => $this->customer->email,
                'customer_id' => $this->customer->id,
            ]
        )->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertQueued(ConversationTranscriptMail::class, function (ConversationTranscriptMail $mail) {
            return $mail->recipientEmail === $this->customer->email;
        });
    }

    public function test_transcript_email_matching_is_case_insensitive(): void
    {
        Mail::fake();
        $this->buildConversationWithWeb(enableTranscripts: true);

        $this->postJson(
            route('helpdesk-livechat.widget.conversation.email-transcript', $this->conversation->id),
            [
                'email' => strtoupper($this->customer->email),
                'customer_id' => $this->customer->id,
            ]
        )->assertOk();

        Mail::assertQueued(ConversationTranscriptMail::class);
    }

    /**
     * Regresión: si el email del cliente quedó guardado con espacios sobrantes,
     * la comparación fallaba (403) aunque el destino fuera el mismo. Ahora se
     * hace trim() a ambos lados.
     */
    public function test_transcript_matches_despite_whitespace_in_stored_email(): void
    {
        Mail::fake();
        $this->buildConversationWithWeb(enableTranscripts: true);

        // Email del cliente persistido con espacios (el destino ya lo limpia TrimStrings).
        $this->customer->forceFill(['email' => '  '.$this->customer->email.'  '])->save();

        $this->postJson(
            route('helpdesk-livechat.widget.conversation.email-transcript', $this->conversation->id),
            [
                'email' => trim($this->customer->email),
                'customer_id' => $this->customer->id,
            ]
        )->assertOk();

        Mail::assertQueued(ConversationTranscriptMail::class);
    }
}

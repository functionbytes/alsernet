<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Events\AiAnswerRated;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * 👍/👎 sobre una respuesta del agente IA en el widget:
 * `POST /hd/api/conversation/{id}/messages/{itemId}/feedback`.
 *
 * El item debe pertenecer a la conversación autorizada (X-Conversation-Token)
 * Y estar marcado como IA (metadata.ai_agent) — cualquier otra combinación
 * es 404/422/401, nunca escribe metadata.
 */
class WidgetAiAnswerFeedbackTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private const TOKEN = 'pubsub_ai_feedback_token';

    private function conversation(): Conversation
    {
        $this->seedOpenConversationStatus();
        $web = WebFactory::new()->create();
        $customer = Customer::factory()->create(['email' => 'ai-feedback-'.Str::random(8).'@example.com']);

        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Widget Test', 'is_active' => true]
        );

        return Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'metadata' => ['widget_pubsub_token' => self::TOKEN],
        ]);
    }

    private function aiItem(Conversation $conversation, array $metadata = []): ConversationItem
    {
        return ConversationItem::create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'body' => 'La respuesta generada por el agente IA.',
            'is_internal' => false,
            'metadata' => array_merge(['sent_by_chatflow' => true, 'ai_agent' => true], $metadata),
        ]);
    }

    private function feedbackUrl(int $conversationId, int $itemId): string
    {
        return route('helpdesk-livechat.widget.conversation.messages.feedback', [$conversationId, $itemId]);
    }

    public function test_visitor_can_rate_ai_answer_up(): void
    {
        $conversation = $this->conversation();
        $item = $this->aiItem($conversation);

        $response = $this->withHeaders(['X-Conversation-Token' => self::TOKEN])
            ->postJson($this->feedbackUrl($conversation->id, $item->id), ['value' => 'up'])
            ->assertOk();

        $response->assertJson(['success' => true, 'data' => ['ai_feedback' => 1]]);

        $item->refresh();
        $this->assertSame(1, $item->metadata['ai_feedback']);
        $this->assertNotEmpty($item->metadata['ai_feedback_at']);
    }

    public function test_visitor_can_rate_ai_answer_down(): void
    {
        $conversation = $this->conversation();
        $item = $this->aiItem($conversation);

        $this->withHeaders(['X-Conversation-Token' => self::TOKEN])
            ->postJson($this->feedbackUrl($conversation->id, $item->id), ['value' => 'down'])
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['ai_feedback' => -1]]);

        $this->assertSame(-1, $item->refresh()->metadata['ai_feedback']);
    }

    public function test_rating_is_idempotent_and_overwrites_previous_value(): void
    {
        $conversation = $this->conversation();
        $item = $this->aiItem($conversation, ['ai_feedback' => -1, 'ai_feedback_at' => now()->subDay()->toIso8601String()]);

        $this->withHeaders(['X-Conversation-Token' => self::TOKEN])
            ->postJson($this->feedbackUrl($conversation->id, $item->id), ['value' => 'up'])
            ->assertOk()
            ->assertJson(['data' => ['ai_feedback' => 1]]);

        $this->assertSame(1, $item->refresh()->metadata['ai_feedback']);
    }

    public function test_item_belonging_to_another_conversation_returns_404(): void
    {
        $conversation = $this->conversation();
        $otherConversation = $this->conversation();
        $itemFromOtherConversation = $this->aiItem($otherConversation);

        $this->withHeaders(['X-Conversation-Token' => self::TOKEN])
            ->postJson($this->feedbackUrl($conversation->id, $itemFromOtherConversation->id), ['value' => 'up'])
            ->assertNotFound();
    }

    public function test_non_ai_item_returns_422(): void
    {
        $conversation = $this->conversation();
        $humanItem = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'author_id' => $conversation->customer_id,
            'type' => 'message',
            'body' => 'Hola',
            'is_internal' => false,
        ]);

        $this->withHeaders(['X-Conversation-Token' => self::TOKEN])
            ->postJson($this->feedbackUrl($conversation->id, $humanItem->id), ['value' => 'up'])
            ->assertStatus(422);

        $this->assertArrayNotHasKey('ai_feedback', $humanItem->refresh()->metadata ?? []);
    }

    public function test_request_without_conversation_token_is_unauthorized(): void
    {
        $conversation = $this->conversation();
        $item = $this->aiItem($conversation);

        $this->postJson($this->feedbackUrl($conversation->id, $item->id), ['value' => 'up'])
            ->assertStatus(401);
    }

    public function test_dispatches_ai_answer_rated_event(): void
    {
        Event::fake([AiAnswerRated::class]);

        $conversation = $this->conversation();
        $item = $this->aiItem($conversation);

        $this->withHeaders(['X-Conversation-Token' => self::TOKEN])
            ->postJson($this->feedbackUrl($conversation->id, $item->id), ['value' => 'down'])
            ->assertOk();

        Event::assertDispatched(AiAnswerRated::class, function (AiAnswerRated $event) use ($item) {
            return $event->item->id === $item->id && $event->value === -1;
        });
    }

    public function test_bot_contract_exposes_ai_and_ai_feedback_flags(): void
    {
        $conversation = $this->conversation();
        $item = $this->aiItem($conversation);

        $this->withHeaders(['X-Conversation-Token' => self::TOKEN])
            ->postJson($this->feedbackUrl($conversation->id, $item->id), ['value' => 'up'])
            ->assertOk();

        $response = $this->withHeaders(['X-Conversation-Token' => self::TOKEN])->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $conversation->id)
                .'?customer_email='.urlencode($conversation->customer->email)
        )->assertOk();

        $message = $response->json('data.messages.0');

        $this->assertTrue($message['ai']);
        $this->assertSame(1, $message['ai_feedback']);
    }

    public function test_bot_contract_reports_null_ai_feedback_before_rating(): void
    {
        $conversation = $this->conversation();
        $this->aiItem($conversation);

        $response = $this->withHeaders(['X-Conversation-Token' => self::TOKEN])->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $conversation->id)
                .'?customer_email='.urlencode($conversation->customer->email)
        )->assertOk();

        $message = $response->json('data.messages.0');

        $this->assertTrue($message['ai']);
        $this->assertNull($message['ai_feedback']);
    }
}

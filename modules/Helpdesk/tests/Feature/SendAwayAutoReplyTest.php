<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Listeners\SendAwayAutoReply;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\AgentPresenceService;
use Modules\Helpdesk\Services\OutboundMessageService;
use Tests\TestCase;

/**
 * Tests for the away-mode auto-reply (#60): when a customer writes to a
 * conversation whose assigned agent is away and has an away message, the
 * listener replies once (throttled) on external channels. OutboundMessageService
 * is mocked so no real WhatsApp/Meta API calls are made.
 */
class SendAwayAutoReplyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private AgentPresenceService $presence;

    private ?int $agentId = null;

    protected function setUp(): void
    {
        parent::setUp();
        // El listener consulta el estado de presencia vía Redis (TTL real).
        config(['database.redis.default.password' => '6cWRY1PUmiwYciQxJXkg']);
        $this->presence = app(AgentPresenceService::class);
    }

    protected function tearDown(): void
    {
        Redis::del('helpdesk:presence:agents:'.($this->agentId ?? 0));
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    private function awayAgent(string $message = 'Estoy ausente'): User
    {
        $agent = User::factory()->create();
        $this->agentId = $agent->id;
        $this->presence->setState($agent->id, AgentSettings::PRESENCE_AWAY, ['away_message' => $message]);

        return $agent;
    }

    private function whatsappConversation(int $assigneeId): Conversation
    {
        return Conversation::factory()->create([
            'assignee_id' => $assigneeId,
            'channel' => 'whatsapp',
            'external_sender_id' => '34600000000',
        ]);
    }

    private function fire(Conversation $conversation): void
    {
        $item = ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'type' => 'message',
        ]);

        (new SendAwayAutoReply)->handle(new MessageReceived($conversation, $item));
    }

    private function mockOutbound(bool $supports, ?string $sendReturn, int $sendTimes): void
    {
        $mock = Mockery::mock(OutboundMessageService::class);
        $mock->shouldReceive('supports')->andReturn($supports);
        $mock->shouldReceive('sendReply')->times($sendTimes)->andReturn($sendReturn);
        $this->app->instance(OutboundMessageService::class, $mock);
    }

    public function test_sends_auto_reply_when_assigned_agent_is_away(): void
    {
        $agent = $this->awayAgent('Estoy ausente, vuelvo pronto');
        $conversation = $this->whatsappConversation($agent->id);
        $this->mockOutbound(supports: true, sendReturn: 'wamid.1', sendTimes: 1);

        $this->fire($conversation);

        $this->assertDatabaseHas('helpdesk_conversation_items', [
            'conversation_id' => $conversation->id,
            'user_id' => $agent->id,
            'body' => 'Estoy ausente, vuelvo pronto',
        ], 'helpdesk');
    }

    public function test_does_not_reply_when_agent_is_available(): void
    {
        $agent = User::factory()->create();
        $this->agentId = $agent->id;
        $this->presence->setState($agent->id, AgentSettings::PRESENCE_AVAILABLE, ['away_message' => 'Ausente']);
        $conversation = $this->whatsappConversation($agent->id);
        $this->mockOutbound(supports: true, sendReturn: null, sendTimes: 0);

        $this->fire($conversation);

        $this->assertDatabaseMissing('helpdesk_conversation_items', [
            'conversation_id' => $conversation->id,
            'user_id' => $agent->id,
        ], 'helpdesk');
    }

    public function test_does_not_reply_without_away_message(): void
    {
        $agent = $this->awayAgent('');
        $conversation = $this->whatsappConversation($agent->id);
        $this->mockOutbound(supports: true, sendReturn: null, sendTimes: 0);

        $this->fire($conversation);

        $this->assertDatabaseMissing('helpdesk_conversation_items', [
            'conversation_id' => $conversation->id,
            'user_id' => $agent->id,
        ], 'helpdesk');
    }

    public function test_replies_on_web_channel_even_though_api_send_is_noop(): void
    {
        $agent = $this->awayAgent('Estoy ausente');
        $conversation = Conversation::factory()->create([
            'assignee_id' => $agent->id,
            'channel' => 'web',
            'external_sender_id' => null,
        ]);
        // sendReply devuelve null en web, pero el ConversationItem se registra
        // igual (el widget lo recoge por su propio canal).
        $this->mockOutbound(supports: false, sendReturn: null, sendTimes: 1);

        $this->fire($conversation);

        $this->assertDatabaseHas('helpdesk_conversation_items', [
            'conversation_id' => $conversation->id,
            'user_id' => $agent->id,
            'body' => 'Estoy ausente',
        ], 'helpdesk');
    }

    public function test_throttles_to_one_reply_per_conversation(): void
    {
        $agent = $this->awayAgent();
        $conversation = $this->whatsappConversation($agent->id);
        $this->mockOutbound(supports: true, sendReturn: 'wamid.1', sendTimes: 1);

        $this->fire($conversation);
        $this->fire($conversation);

        $count = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $agent->id)
            ->count();

        $this->assertSame(1, $count);
    }
}

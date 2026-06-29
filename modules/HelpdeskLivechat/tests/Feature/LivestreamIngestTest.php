<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Events\LivestreamBatchReceived;
use Tests\TestCase;

class LivestreamIngestTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private const CONV_TOKEN = 'pubsub_livestream_token';

    private function createWebContext(bool $enableLiveView = true): array
    {
        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $web = WebFactory::new()->create(['enable_live_view' => $enableLiveView]);

        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Web Inbox', 'is_active' => true]
        );

        $customer = Customer::factory()->create();

        $conversation = Conversation::create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'status_id' => $status->id,
            'subject' => 'Test',
            'last_message_at' => now(),
            'metadata' => json_encode(['widget_pubsub_token' => self::CONV_TOKEN]),
        ]);

        return [$web, $inbox, $conversation];
    }

    public function test_ingest_with_valid_token_persists_and_dispatches_event(): void
    {
        Event::fake([LivestreamBatchReceived::class]);

        [$web, , $conversation] = $this->createWebContext();

        $payload = [
            'events' => [
                ['type' => 2, 'data' => ['href' => 'https://example.com'], 'timestamp' => now()->getTimestamp() * 1000],
                ['type' => 3, 'data' => ['source' => 1], 'timestamp' => now()->getTimestamp() * 1000 + 100],
            ],
            'batch_index' => 0,
        ];

        $response = $this->withHeaders(['X-Website-Token' => $web->website_token, 'X-Conversation-Token' => self::CONV_TOKEN])
            ->postJson(route('helpdesk-livechat.widget.livestream.ingest', $conversation), $payload);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('helpdesk_livestream_events', [
            'conversation_id' => $conversation->id,
            'batch_index' => 0,
        ], 'helpdesk');

        Event::assertDispatched(LivestreamBatchReceived::class, fn ($e) => $e->conversationId === $conversation->id && $e->batchIndex === 0);
    }

    public function test_ingest_with_invalid_token_returns_403(): void
    {
        [, , $conversation] = $this->createWebContext();

        $response = $this->withHeaders(['X-Website-Token' => 'invalid_token', 'X-Conversation-Token' => self::CONV_TOKEN])
            ->postJson(route('helpdesk-livechat.widget.livestream.ingest', $conversation), [
                'events' => [['type' => 2]],
                'batch_index' => 0,
            ]);

        $response->assertStatus(403);
    }

    public function test_ingest_without_conversation_token_returns_403(): void
    {
        [$web, , $conversation] = $this->createWebContext();

        $response = $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.livestream.ingest', $conversation), [
                'events' => [['type' => 2]],
                'batch_index' => 0,
            ]);

        $response->assertStatus(403);
    }

    public function test_ingest_with_wrong_conversation_token_returns_403(): void
    {
        [$web, , $conversation] = $this->createWebContext();

        $response = $this->withHeaders(['X-Website-Token' => $web->website_token, 'X-Conversation-Token' => 'wrong'])
            ->postJson(route('helpdesk-livechat.widget.livestream.ingest', $conversation), [
                'events' => [['type' => 2]],
                'batch_index' => 0,
            ]);

        $response->assertStatus(403);
    }

    public function test_ingest_when_live_view_disabled_returns_403(): void
    {
        [$web, , $conversation] = $this->createWebContext(enableLiveView: false);

        $response = $this->withHeaders(['X-Website-Token' => $web->website_token, 'X-Conversation-Token' => self::CONV_TOKEN])
            ->postJson(route('helpdesk-livechat.widget.livestream.ingest', $conversation), [
                'events' => [['type' => 2]],
                'batch_index' => 0,
            ]);

        $response->assertStatus(403);
    }

    public function test_ingest_validates_events_array_required(): void
    {
        [$web, , $conversation] = $this->createWebContext();

        $response = $this->withHeaders(['X-Website-Token' => $web->website_token, 'X-Conversation-Token' => self::CONV_TOKEN])
            ->postJson(route('helpdesk-livechat.widget.livestream.ingest', $conversation), [
                'events' => [],
                'batch_index' => 0,
            ]);

        $response->assertStatus(422);
    }
}

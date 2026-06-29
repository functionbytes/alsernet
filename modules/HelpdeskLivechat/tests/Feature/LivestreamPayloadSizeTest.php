<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Jobs\StoreLivestreamBatchJob;
use Tests\TestCase;

/**
 * Regression tests for the 256 KB payload limit in IngestLivestreamEventsRequest.
 *
 * When json_encode(events) exceeds 256 KB the request must be rejected with
 * 422 and the error must mention "Payload too large". A batch within limits
 * must be accepted and the StoreLivestreamBatchJob dispatched.
 */
class LivestreamPayloadSizeTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private const CONV_TOKEN = 'pubsub_payloadsize_token';

    private string $websiteToken;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $web = WebFactory::new()->create(['enable_live_view' => true]);

        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Livestream Test Inbox', 'is_active' => true]
        );

        $customer = Customer::factory()->create();

        $this->conversation = Conversation::create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'status_id' => $status->id,
            'subject' => 'Livestream test',
            'last_message_at' => now(),
            'metadata' => json_encode(['widget_pubsub_token' => self::CONV_TOKEN]),
        ]);

        $this->websiteToken = $web->website_token;
    }

    // -----------------------------------------------------------------------
    // Failure path: payload > 256 KB
    // -----------------------------------------------------------------------

    public function test_payload_exceeding_256kb_returns_422(): void
    {
        // Build events array whose JSON representation exceeds 256 KB.
        $bigValue = str_repeat('x', 1024); // 1 KB per event
        $events = [];
        for ($i = 0; $i < 260; $i++) {
            $events[] = ['type' => 2, 'data' => $bigValue, 'timestamp' => now()->getTimestampMs()];
        }

        $response = $this->withHeaders(['X-Website-Token' => $this->websiteToken, 'X-Conversation-Token' => self::CONV_TOKEN])
            ->postJson(route('helpdesk-livechat.widget.livestream.ingest', $this->conversation), [
                'events' => $events,
                'batch_index' => 0,
            ]);

        $response->assertUnprocessable();

        $errors = $response->json('errors.events') ?? [];
        $foundPayloadError = collect($errors)->contains(
            fn (string $msg) => str_contains(strtolower($msg), 'payload') || str_contains(strtolower($msg), 'large')
        );

        $this->assertTrue($foundPayloadError, 'Expected a "Payload too large" validation error on events');
    }

    // -----------------------------------------------------------------------
    // Happy path: small batch dispatches the job
    // -----------------------------------------------------------------------

    public function test_small_batch_dispatches_store_livestream_batch_job(): void
    {
        Queue::fake();

        $events = [
            ['type' => 2, 'data' => ['href' => 'https://example.com'], 'timestamp' => now()->getTimestampMs()],
            ['type' => 3, 'data' => ['source' => 1], 'timestamp' => now()->getTimestampMs() + 100],
        ];

        $response = $this->withHeaders(['X-Website-Token' => $this->websiteToken, 'X-Conversation-Token' => self::CONV_TOKEN])
            ->postJson(route('helpdesk-livechat.widget.livestream.ingest', $this->conversation), [
                'events' => $events,
                'batch_index' => 0,
            ]);

        $response->assertSuccessful();

        Queue::assertPushed(
            StoreLivestreamBatchJob::class,
            fn (StoreLivestreamBatchJob $job) => $job->conversationId === $this->conversation->id
        );
    }
}

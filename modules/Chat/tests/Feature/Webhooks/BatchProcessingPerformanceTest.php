<?php

namespace Modules\Chat\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Modules\Chat\Jobs\Webhooks\ProcessAttachmentJob;
use Modules\Chat\Jobs\Webhooks\ProcessFacebookMessageJobOptimized;
use Modules\Chat\Models\Channels\Facebook;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Services\WebhookCacheService;
use Modules\Chat\Traits\BatchProcessingTrait;
use Tests\TestCase;

class BatchProcessingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected Facebook $facebookPage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facebookPage = Facebook::factory()->create([
            'account_id' => 1,
            'page_id' => 'test_page_123',
            'page_access_token' => 'test_token',
        ]);
    }

    /** @test */
    public function it_processes_single_message_under_100ms(): void
    {
        Queue::fake();

        $event = [
            'sender' => ['id' => 'test_sender_1'],
            'message' => ['text' => 'Test message', 'mid' => 'mid_1'],
        ];

        $startTime = microtime(true);

        ProcessFacebookMessageJobOptimized::dispatch($this->facebookPage, $event);

        $dispatchTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(100, $dispatchTime, 'Single message dispatch should take < 100ms');
    }

    /** @test */
    public function it_processes_100_messages_under_1500ms(): void
    {
        Queue::fake();
        Bus::fake();

        $events = [];
        for ($i = 0; $i < 100; $i++) {
            $events[] = [
                'sender' => ['id' => 'test_sender_bulk'],
                'message' => ['text' => "Message {$i}", 'mid' => "mid_{$i}"],
            ];
        }

        $startTime = microtime(true);

        $job = new ProcessFacebookMessageJobOptimized($this->facebookPage, $events);
        $job->handle(app(WebhookCacheService::class));

        $processingTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(1500, $processingTime, '100 messages should process < 1500ms');
        $this->assertEquals(100, ConversationMessage::count(), 'Should create 100 messages');
    }

    /** @test */
    public function it_dispatches_parallel_attachment_jobs(): void
    {
        Bus::fake();

        $events = [
            [
                'sender' => ['id' => 'test_sender_attach'],
                'message' => [
                    'text' => 'Message with attachment',
                    'mid' => 'mid_attach_1',
                    'attachments' => [
                        ['type' => 'image', 'payload' => ['url' => 'https://example.com/image1.jpg']],
                        ['type' => 'image', 'payload' => ['url' => 'https://example.com/image2.jpg']],
                    ],
                ],
            ],
        ];

        $job = new ProcessFacebookMessageJobOptimized($this->facebookPage, $events);
        $job->handle(app(WebhookCacheService::class));

        Bus::assertBatched(function ($batch) {
            return $batch->jobs->count() === 2
                && $batch->jobs[0] instanceof ProcessAttachmentJob
                && $batch->name === 'Facebook Attachments - Conversation '.Conversation::first()->id;
        });
    }

    /** @test */
    public function it_groups_messages_by_sender(): void
    {
        Queue::fake();

        $events = [
            ['sender' => ['id' => 'sender_1'], 'message' => ['text' => 'Msg 1', 'mid' => 'mid_1']],
            ['sender' => ['id' => 'sender_2'], 'message' => ['text' => 'Msg 2', 'mid' => 'mid_2']],
            ['sender' => ['id' => 'sender_1'], 'message' => ['text' => 'Msg 3', 'mid' => 'mid_3']],
        ];

        $job = new ProcessFacebookMessageJobOptimized($this->facebookPage, $events);
        $job->handle(app(WebhookCacheService::class));

        // Should create 2 customers (2 unique senders)
        $this->assertEquals(2, Customer::count());

        // Should create 2 conversations
        $this->assertEquals(2, Conversation::count());

        // Should create 3 messages total
        $this->assertEquals(3, ConversationMessage::count());

        // Sender 1 should have 2 messages
        $customer1 = Customer::where('identifier', 'facebook_sender_1')->first();
        $this->assertEquals(2, ConversationMessage::where('sender_id', $customer1->id)->count());
    }

    /** @test */
    public function it_uses_cache_for_repeat_customers(): void
    {
        $cacheService = app(WebhookCacheService::class);

        // First message creates customer
        $events1 = [
            ['sender' => ['id' => 'cached_sender'], 'message' => ['text' => 'First', 'mid' => 'mid_1']],
        ];

        $job1 = new ProcessFacebookMessageJobOptimized($this->facebookPage, $events1);
        $job1->handle($cacheService);

        $queryCount1 = \DB::getQueryLog();

        // Second message should use cache
        \DB::enableQueryLog();
        $events2 = [
            ['sender' => ['id' => 'cached_sender'], 'message' => ['text' => 'Second', 'mid' => 'mid_2']],
        ];

        $job2 = new ProcessFacebookMessageJobOptimized($this->facebookPage, $events2);
        $job2->handle($cacheService);

        $queryCount2 = \DB::getQueryLog();
        \DB::disableQueryLog();

        // Should have fewer queries on second run (cache hit)
        $this->assertLessThan(count($queryCount1), count($queryCount2));
        $this->assertEquals(1, Customer::count(), 'Should reuse existing customer');
        $this->assertEquals(2, ConversationMessage::count(), 'Should create 2 messages');
    }

    /** @test */
    public function it_handles_memory_efficiently_for_large_batches(): void
    {
        $memoryBefore = memory_get_usage(true);

        $events = [];
        for ($i = 0; $i < 500; $i++) {
            $events[] = [
                'sender' => ['id' => 'memory_test_sender'],
                'message' => ['text' => str_repeat('A', 1000), 'mid' => "mid_{$i}"], // 1KB each
            ];
        }

        $job = new ProcessFacebookMessageJobOptimized($this->facebookPage, $events);
        $job->handle(app(WebhookCacheService::class));

        $memoryAfter = memory_get_peak_usage(true);
        $memoryUsedMB = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        $this->assertLessThan(100, $memoryUsedMB, 'Should use < 100MB for 500 messages');
        $this->assertEquals(500, ConversationMessage::count());
    }

    /** @test */
    public function it_calculates_optimal_batch_sizes(): void
    {
        $trait = new class
        {
            use BatchProcessingTrait;

            public function publicCalculateBatchSize(array $messages): int
            {
                return $this->calculateBatchSize($messages);
            }
        };

        // Text-only messages: should batch up to 50
        $textMessages = array_fill(0, 100, [
            'message' => ['text' => 'Test'],
        ]);
        $this->assertEquals(50, $trait->publicCalculateBatchSize($textMessages));

        // Messages with attachments: should batch up to 20
        $attachMessages = array_fill(0, 100, [
            'message' => ['attachments' => [['type' => 'image']]],
        ]);
        $this->assertEquals(20, $trait->publicCalculateBatchSize($attachMessages));

        // Single message: batch size 1
        $singleMessage = [
            ['message' => ['text' => 'Single']],
        ];
        $this->assertEquals(1, $trait->publicCalculateBatchSize($singleMessage));
    }

    /** @test */
    public function it_logs_batch_metrics(): void
    {
        \Log::spy();

        $events = array_fill(0, 50, [
            'sender' => ['id' => 'metrics_sender'],
            'message' => ['text' => 'Test', 'mid' => uniqid('mid_')],
        ]);

        $job = new ProcessFacebookMessageJobOptimized($this->facebookPage, $events);
        $job->handle(app(WebhookCacheService::class));

        \Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'BATCH METRICS')
                    && isset($context['throughput_msgs_per_sec'])
                    && isset($context['memory_peak_mb']);
            })
            ->once();
    }
}

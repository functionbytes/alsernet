<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Engagement\Models\VisitorSession;
use Modules\Engagement\Services\TrackingIngestService;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Listeners\EngagementBridgeListener;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

/**
 * Tests for the Engagement ↔ HelpdeskLivechat bridge listener.
 *
 * Strategy: we swap the TrackingIngestService binding so we can assert calls
 * without hitting the actual Engagement tracking pipeline. DB-heavy tests that
 * require full conversation setup use RefreshDatabase; pure-logic tests mock
 * Module::find() to avoid real module resolution.
 */
class EngagementBridgeTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private ConversationStatus $openStatus;

    private Web $web;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openStatus = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->web = WebFactory::new()->create();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Create a minimal web-channel conversation with a linked visitor session.
     */
    private function createWebConversation(): Conversation
    {
        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $this->web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Test Widget', 'is_active' => true]
        );

        $customer = Customer::factory()->create();

        $token = 'test_session_token_'.uniqid();

        $conversation = Conversation::create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'status_id' => $this->openStatus->id,
            'subject' => 'Test conversation',
            'last_message_at' => now(),
            'metadata' => json_encode(['widget_pubsub_token' => $token]),
        ]);

        // Create a matching visitor session so the bridge can resolve the token.
        VisitorSession::create([
            'session_token' => $token,
            'inbox_id' => $inbox->id,
            'customer_id' => $customer->id,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);

        return $conversation;
    }

    private function mockEngagementEnabled(bool $enabled): void
    {
        if ($enabled) {
            $moduleMock = \Mockery::mock();
            $moduleMock->shouldReceive('isEnabled')->andReturn(true);
            Module::shouldReceive('find')->with('Engagement')->andReturn($moduleMock);
        } else {
            Module::shouldReceive('find')->with('Engagement')->andReturn(null);
        }
    }

    // -----------------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------------

    public function test_listener_skipped_when_engagement_disabled(): void
    {
        $this->mockEngagementEnabled(false);

        $called = false;
        $this->app->bind(TrackingIngestService::class, function () use (&$called) {
            $called = true;

            return new TrackingIngestService;
        });

        $conversation = $this->createWebConversation();
        $event = new ConversationCreated($conversation);

        $listener = new EngagementBridgeListener;
        $listener->handleConversationCreated($event);

        $this->assertFalse($called, 'TrackingIngestService should not be resolved when Engagement is disabled');
    }

    public function test_conversation_created_dispatches_to_engagement(): void
    {
        $this->mockEngagementEnabled(true);

        $ingestedEvents = [];
        $this->app->bind(TrackingIngestService::class, function () use (&$ingestedEvents) {
            $mock = \Mockery::mock(TrackingIngestService::class);
            $mock->shouldReceive('ingest')
                ->once()
                ->andReturnUsing(function (array $events, string $token, Inbox $inbox) use (&$ingestedEvents) {
                    $ingestedEvents = $events;

                    return 1;
                });

            return $mock;
        });

        $conversation = $this->createWebConversation();
        $event = new ConversationCreated($conversation);

        $listener = new EngagementBridgeListener;
        $listener->handleConversationCreated($event);

        $this->assertCount(1, $ingestedEvents);
        $this->assertSame('conversation.started', $ingestedEvents[0]['name']);
        $this->assertSame($conversation->id, $ingestedEvents[0]['properties']['conversation_id']);
        $this->assertSame('web', $ingestedEvents[0]['properties']['channel_type']);
        $this->assertSame($conversation->customer_id, $ingestedEvents[0]['properties']['customer_id']);
    }

    public function test_message_from_agent_does_not_dispatch(): void
    {
        $this->mockEngagementEnabled(true);

        $this->app->bind(TrackingIngestService::class, function () {
            $mock = \Mockery::mock(TrackingIngestService::class);
            $mock->shouldNotReceive('ingest');

            return $mock;
        });

        $conversation = $this->createWebConversation();

        // Agent message: has user_id, no author_id (from customer).
        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'user_id' => 1,
            'author_id' => null,
            'type' => 'message',
            'body' => 'Hello from agent',
            'is_internal' => false,
        ]);

        $event = new ConversationMessageCreated($item, false);

        $listener = new EngagementBridgeListener;
        $listener->handleMessageCreated($event);

        $this->assertTrue(true);
    }

    public function test_message_from_customer_dispatches_to_engagement(): void
    {
        $this->mockEngagementEnabled(true);

        $ingestedEvents = [];
        $this->app->bind(TrackingIngestService::class, function () use (&$ingestedEvents) {
            $mock = \Mockery::mock(TrackingIngestService::class);
            $mock->shouldReceive('ingest')
                ->once()
                ->andReturnUsing(function (array $events) use (&$ingestedEvents) {
                    $ingestedEvents = $events;

                    return 1;
                });

            return $mock;
        });

        $conversation = $this->createWebConversation();

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'author_id' => $conversation->customer_id,
            'user_id' => null,
            'type' => 'message',
            'body' => 'Hello, I need help',
            'is_internal' => false,
        ]);

        $event = new ConversationMessageCreated($item, false);

        $listener = new EngagementBridgeListener;
        $listener->handleMessageCreated($event);

        $this->assertCount(1, $ingestedEvents);
        $this->assertSame('conversation.message_sent', $ingestedEvents[0]['name']);
        $this->assertSame($conversation->id, $ingestedEvents[0]['properties']['conversation_id']);
        $this->assertFalse($ingestedEvents[0]['properties']['has_attachments']);
    }

    public function test_close_with_rating_dispatches_with_rating_property(): void
    {
        $this->mockEngagementEnabled(true);

        $ingestedEvents = [];
        $this->app->bind(TrackingIngestService::class, function () use (&$ingestedEvents) {
            $mock = \Mockery::mock(TrackingIngestService::class);
            $mock->shouldReceive('ingest')
                ->once()
                ->andReturnUsing(function (array $events) use (&$ingestedEvents) {
                    $ingestedEvents = $events;

                    return 1;
                });

            return $mock;
        });

        $conversation = $this->createWebConversation();
        $conversation->update(['closed_at' => now()]);
        $conversation->refresh();

        // Seed a CSAT rating.
        CsatRating::create([
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'rating' => 5,
            'answered_at' => now(),
        ]);

        $event = new ConversationClosed($conversation);

        $listener = new EngagementBridgeListener;
        $listener->handleConversationClosed($event);

        $this->assertCount(1, $ingestedEvents);
        $this->assertSame('conversation.closed', $ingestedEvents[0]['name']);
        $this->assertSame(5, $ingestedEvents[0]['properties']['rating']);
        $this->assertTrue($ingestedEvents[0]['properties']['feedback_provided']);
    }

    public function test_close_without_session_does_not_dispatch(): void
    {
        $this->mockEngagementEnabled(true);

        $this->app->bind(TrackingIngestService::class, function () {
            $mock = \Mockery::mock(TrackingIngestService::class);
            $mock->shouldNotReceive('ingest');

            return $mock;
        });

        // Conversation with no matching visitor session.
        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $this->web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Test Widget', 'is_active' => true]
        );

        $customer = Customer::factory()->create();
        $conversation = Conversation::create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'status_id' => $this->openStatus->id,
            'subject' => 'No session conversation',
            'last_message_at' => now(),
            'metadata' => json_encode(['widget_pubsub_token' => 'unmatched_token_xyz']),
        ]);
        // No VisitorSession created — bridge should silently skip.

        $event = new ConversationClosed($conversation);

        $listener = new EngagementBridgeListener;
        $listener->handleConversationClosed($event);

        $this->assertTrue(true);
    }
}

<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Listeners\EngagementBridgeListener;
use Modules\HelpdeskLivechat\Providers\HelpdeskLivechatServiceProvider;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regression coverage for the Settings → Integraciones kill switch
 * (`livechat.integration_enabled`) gating the Engagement bridge listeners.
 *
 * HelpdeskLivechatServiceProvider must not wire ConversationCreated (and its
 * siblings) to EngagementBridgeListener while the integration is disabled,
 * otherwise a queued listener keeps reacting to helpdesk activity even after
 * the admin turns the integration off in Settings → Integraciones.
 */
class LivechatIntegrationToggleGatesListenersTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_conversation_created_listener_is_not_queued_when_integration_disabled(): void
    {
        Setting::set('livechat.integration_enabled', '0', 'integrations');

        Queue::fake();
        $this->reregisterEngagementBridgeListeners();

        event(new ConversationCreated($this->createWebConversation()));

        Queue::assertNotPushed(
            CallQueuedListener::class,
            fn (CallQueuedListener $job) => $job->class === EngagementBridgeListener::class
                && $job->method === 'handleConversationCreated',
        );
    }

    public function test_conversation_created_listener_is_queued_when_integration_enabled(): void
    {
        Setting::set('livechat.integration_enabled', '1', 'integrations');

        Queue::fake();
        $this->reregisterEngagementBridgeListeners();

        event(new ConversationCreated($this->createWebConversation()));

        Queue::assertPushed(
            CallQueuedListener::class,
            fn (CallQueuedListener $job) => $job->class === EngagementBridgeListener::class
                && $job->method === 'handleConversationCreated',
        );
    }

    /**
     * Simulate a fresh provider boot re-evaluating the toggle: forget any
     * listeners registered during the real application bootstrap, then
     * re-run the gated registration method under the current Setting value.
     */
    private function reregisterEngagementBridgeListeners(): void
    {
        Event::forget(ConversationCreated::class);

        $provider = new HelpdeskLivechatServiceProvider($this->app);

        $method = new ReflectionMethod($provider, 'registerEngagementBridgeListeners');
        $method->setAccessible(true);
        $method->invoke($provider);
    }

    private function createWebConversation(): Conversation
    {
        $status = $this->seedOpenConversationStatus();

        $web = WebFactory::new()->create();

        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Test Widget', 'is_active' => true],
        );

        $customer = Customer::factory()->create();

        return Conversation::create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'status_id' => $status->id,
            'subject' => 'Test conversation',
            'last_message_at' => now(),
        ]);
    }
}

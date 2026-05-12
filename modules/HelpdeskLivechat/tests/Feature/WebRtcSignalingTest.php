<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Events\WebRtcSignal;
use Tests\TestCase;

class WebRtcSignalingTest extends TestCase
{
    use RefreshDatabase;

    private function createWebContext(bool $enableScreenShare = true): array
    {
        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $web = WebFactory::new()->create(['enable_screen_share' => $enableScreenShare]);

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
            'metadata' => json_encode(['widget_pubsub_token' => 'pubsub_'.uniqid()]),
        ]);

        return [$web, $conversation];
    }

    public function test_offer_dispatches_webrtc_signal_event_to_agent(): void
    {
        Event::fake([WebRtcSignal::class]);

        [$web, $conversation] = $this->createWebContext();

        $sdp = str_repeat('v=0'.PHP_EOL, 20);
        $response = $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.webrtc.offer', $conversation), [
                'sdp' => $sdp,
                'type' => 'offer',
            ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        Event::assertDispatched(
            WebRtcSignal::class,
            fn ($e) => $e->conversationId === $conversation->id
                && $e->type === 'offer'
                && $e->direction === 'to-agent'
        );
    }

    public function test_offer_with_invalid_token_returns_403(): void
    {
        [, $conversation] = $this->createWebContext();

        $response = $this->withHeaders(['X-Website-Token' => 'bad'])
            ->postJson(route('helpdesk-livechat.widget.webrtc.offer', $conversation), [
                'sdp' => str_repeat('v=0', 20),
                'type' => 'offer',
            ]);

        $response->assertStatus(403);
    }

    public function test_ice_candidate_dispatches_signal(): void
    {
        Event::fake([WebRtcSignal::class]);

        [$web, $conversation] = $this->createWebContext();

        $response = $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.webrtc.ice', $conversation), [
                'candidate' => [
                    'candidate' => 'candidate:1 1 UDP 2122252543 192.0.2.1 53000 typ host',
                    'sdpMid' => '0',
                    'sdpMLineIndex' => 0,
                ],
            ]);

        $response->assertStatus(201);

        Event::assertDispatched(WebRtcSignal::class, fn ($e) => $e->type === 'ice');
    }

    public function test_when_screen_share_disabled_returns_403(): void
    {
        [$web, $conversation] = $this->createWebContext(enableScreenShare: false);

        $response = $this->withHeaders(['X-Website-Token' => $web->website_token])
            ->postJson(route('helpdesk-livechat.widget.webrtc.offer', $conversation), [
                'sdp' => str_repeat('v=0', 20),
                'type' => 'offer',
            ]);

        $response->assertStatus(403);
    }
}

<?php

namespace Modules\HelpdeskLivechat\Tests\Unit;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Modules\HelpdeskLivechat\Events\WebRtcSignal;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for WebRtcSignal::broadcastOn() — verifies that the agent-facing
 * channel is private and the widget-facing channel uses the token-guarded public
 * channel (helpdesk-widget-conversation.{id}.{pubsubToken}).
 */
class WebRtcSignalChannelTest extends TestCase
{
    public function test_to_agent_direction_uses_private_channel(): void
    {
        $signal = new WebRtcSignal(42, 'offer', ['sdp' => 'v=0'], 'to-agent');

        $channels = $signal->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-webrtc.conversation.42', $channels[0]->name);
    }

    public function test_to_widget_direction_uses_token_guarded_public_channel(): void
    {
        $signal = new WebRtcSignal(7, 'answer', ['sdp' => 'v=0'], 'to-widget', 'abc123token');

        $channels = $signal->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertSame('helpdesk-widget-conversation.7.abc123token', $channels[0]->name);
    }

    public function test_broadcast_as_includes_signal_type(): void
    {
        $offer = new WebRtcSignal(1, 'offer', [], 'to-agent');
        $answer = new WebRtcSignal(1, 'answer', [], 'to-widget', 'tok');
        $ice = new WebRtcSignal(1, 'ice', [], 'to-agent');

        $this->assertSame('webrtc.offer', $offer->broadcastAs());
        $this->assertSame('webrtc.answer', $answer->broadcastAs());
        $this->assertSame('webrtc.ice', $ice->broadcastAs());
    }
}

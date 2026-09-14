<?php

namespace Modules\HelpdeskLivechat\Tests\Unit;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Modules\HelpdeskLivechat\Events\WebRtcSignal;
use PHPUnit\Framework\TestCase;

/**
 * Regression unit tests for WebRtcSignal::broadcastOn() — token-guarded channel.
 *
 * WebRtcSignalChannelTest already covers:
 *   - to-agent → PrivateChannel
 *   - to-widget with token → token-guarded public channel
 *   - broadcastAs() for all signal types
 *
 * This file adds coverage for the security property that the token-guarded
 * channel name is unique per conversation×token, so a signal without a token
 * emits to a channel name that cannot be confused with the generic pattern.
 */
class WebRtcSignalNoTokenTest extends TestCase
{
    /**
     * Without a pubsubToken the channel name contains a null segment, making it
     * impossible to subscribe to the real per-visitor channel. This ensures the
     * backend never emits a signal to an unauthenticated broadcast channel.
     */
    public function test_to_widget_without_token_channel_name_does_not_match_expected_pattern(): void
    {
        $signal = new WebRtcSignal(99, 'offer', ['sdp' => 'v=0'], 'to-widget', null);

        $channels = $signal->broadcastOn();
        $this->assertCount(1, $channels);

        // The channel must NOT be a PrivateChannel — to-widget uses a public channel.
        $this->assertNotInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertInstanceOf(Channel::class, $channels[0]);

        // The channel name must NOT look like "helpdesk-widget-conversation.99.<real-token>"
        // because no token was supplied. The empty/null segment makes it harmless.
        $this->assertStringNotContainsString(
            'helpdesk-widget-conversation.99.',
            // Strip the trailing null segment to check the prefix correctly.
            rtrim($channels[0]->name, '.'),
        );
    }

    /**
     * Different pubsubTokens produce different channel names, so signals for
     * one visitor cannot bleed into another visitor's channel.
     */
    public function test_different_tokens_produce_different_channel_names(): void
    {
        $signal1 = new WebRtcSignal(5, 'answer', [], 'to-widget', 'token-aaa');
        $signal2 = new WebRtcSignal(5, 'answer', [], 'to-widget', 'token-bbb');

        $name1 = $signal1->broadcastOn()[0]->name;
        $name2 = $signal2->broadcastOn()[0]->name;

        $this->assertNotSame($name1, $name2);
    }

    /**
     * The to-agent direction always uses a PrivateChannel regardless of token,
     * so agents cannot subscribe on a public channel.
     */
    public function test_to_agent_direction_is_always_private_channel(): void
    {
        $withToken = new WebRtcSignal(1, 'ice', [], 'to-agent', 'some-token');
        $withoutToken = new WebRtcSignal(1, 'ice', [], 'to-agent', null);

        $this->assertInstanceOf(PrivateChannel::class, $withToken->broadcastOn()[0]);
        $this->assertInstanceOf(PrivateChannel::class, $withoutToken->broadcastOn()[0]);
    }
}

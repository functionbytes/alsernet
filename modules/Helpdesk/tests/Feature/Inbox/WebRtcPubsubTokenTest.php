<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use Illuminate\Support\Facades\Event;
use Modules\HelpdeskLivechat\Events\WebRtcSignal;

/**
 * Regresión BUG-01 item 3: las 4 acciones de WebRtcAgentController (answer,
 * ice, end, request) deben leer widget_pubsub_token de $conversation->metadata
 * y pasarlo como 5º argumento a WebRtcSignal::dispatch. Sin él, WebRtcSignal
 * emite en "helpdesk-widget-conversation.{id}." (token vacío) — un canal al
 * que el widget nunca se suscribe (ver WebRtcSignalNoTokenTest en
 * HelpdeskLivechat), así que la señal se pierde en silencio.
 */
class WebRtcPubsubTokenTest extends InboxTestCase
{
    private const VALID_SDP = 'v=0 o=- 4611731400430051336 2 IN IP4 127.0.0.1 s=- t=0 0';

    private const TOKEN = 'real-visitor-token-abc123';

    public function test_answer_ice_end_and_request_dispatch_the_real_widget_pubsub_token(): void
    {
        Event::fake([WebRtcSignal::class]);

        $conversation = $this->createConversation(['metadata' => ['widget_pubsub_token' => self::TOKEN]]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.webrtc.answer', $conversation), [
                'sdp' => self::VALID_SDP,
                'type' => 'answer',
            ])->assertCreated();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.webrtc.ice', $conversation), [
                'candidate' => ['candidate' => 'candidate:1 1 UDP 2122252543 192.0.2.1 54400 typ host'],
            ])->assertCreated();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.webrtc.end', $conversation))
            ->assertOk();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.webrtc.request', $conversation))
            ->assertOk();

        Event::assertDispatchedTimes(WebRtcSignal::class, 4);
        Event::assertDispatched(WebRtcSignal::class, function (WebRtcSignal $event) use ($conversation) {
            return $event->conversationId === $conversation->id
                && $event->direction === 'to-widget'
                && $event->pubsubToken === self::TOKEN;
        });

        // broadcastOn() debe resolver al canal real por el que escucha el
        // widget, no a uno con el segmento de token vacío.
        $signal = new WebRtcSignal($conversation->id, 'answer', [], 'to-widget', self::TOKEN);
        $this->assertSame(
            'helpdesk-widget-conversation.'.$conversation->id.'.'.self::TOKEN,
            $signal->broadcastOn()[0]->name,
        );
    }

    public function test_dispatches_null_token_when_conversation_has_no_widget_metadata(): void
    {
        Event::fake([WebRtcSignal::class]);

        $conversation = $this->createConversation(['metadata' => null]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.webrtc.answer', $conversation), [
                'sdp' => self::VALID_SDP,
                'type' => 'answer',
            ])->assertCreated();

        Event::assertDispatched(WebRtcSignal::class, fn (WebRtcSignal $event): bool => $event->pubsubToken === null);
    }
}

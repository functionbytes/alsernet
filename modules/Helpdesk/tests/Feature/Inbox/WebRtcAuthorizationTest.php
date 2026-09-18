<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\HelpdeskLivechat\Events\WebRtcSignal;

/**
 * Regresión: answer/ice de la señalización WebRTC exigen la misma
 * autorización (helpdesk.conversations.view) que end/request/livestreamHistory.
 * Antes solo llevaban throttle y cualquier usuario autenticado podía inyectar
 * SDP/ICE hacia el widget de cualquier conversación (IDOR de señalización).
 */
class WebRtcAuthorizationTest extends InboxTestCase
{
    private const VALID_SDP = 'v=0 o=- 4611731400430051336 2 IN IP4 127.0.0.1 s=- t=0 0';

    public function test_user_without_permission_cannot_send_answer(): void
    {
        Event::fake([WebRtcSignal::class]);

        $conversation = $this->createConversation();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.webrtc.answer', $conversation), [
                'sdp' => self::VALID_SDP,
                'type' => 'answer',
            ])
            ->assertForbidden();

        Event::assertNotDispatched(WebRtcSignal::class);
    }

    public function test_user_without_permission_cannot_send_ice(): void
    {
        Event::fake([WebRtcSignal::class]);

        $conversation = $this->createConversation();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.webrtc.ice', $conversation), [
                'candidate' => ['candidate' => 'candidate:1 1 UDP 2122252543 192.0.2.1 54400 typ host'],
            ])
            ->assertForbidden();

        Event::assertNotDispatched(WebRtcSignal::class);
    }

    public function test_authorized_agent_can_send_answer_and_ice(): void
    {
        Event::fake([WebRtcSignal::class]);

        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.webrtc.answer', $conversation), [
                'sdp' => self::VALID_SDP,
                'type' => 'answer',
            ])
            ->assertCreated();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.webrtc.ice', $conversation), [
                'candidate' => ['candidate' => 'candidate:1 1 UDP 2122252543 192.0.2.1 54400 typ host'],
            ])
            ->assertCreated();

        Event::assertDispatchedTimes(WebRtcSignal::class, 2);
    }

    public function test_answer_returns_404_for_missing_conversation(): void
    {
        Event::fake([WebRtcSignal::class]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.webrtc.answer', ['conversation' => 999999999]), [
                'sdp' => self::VALID_SDP,
                'type' => 'answer',
            ])
            ->assertNotFound();

        Event::assertNotDispatched(WebRtcSignal::class);
    }
}

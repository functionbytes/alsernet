<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class WebRtcAgentAuthorizationTest extends HelpdeskTestCase
{
    private function agentWithoutInboxAccess(): User
    {
        $agent = User::factory()->create();
        $agent->givePermissionTo(['helpdesk.view', 'helpdesk.conversations.view']);

        $ownInbox = Inbox::create(['name' => 'Own inbox', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        AgentInboxCapacity::create(['user_id' => $agent->id, 'inbox_id' => $ownInbox->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        return $agent;
    }

    public function test_agent_without_access_to_the_conversation_gets_403_on_signalling_and_history(): void
    {
        $conversation = Conversation::factory()->create(['status_id' => $this->openStatus->id]);
        $agent = $this->agentWithoutInboxAccess();

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversations.webrtc.end', $conversation))
            ->assertForbidden();
        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversations.webrtc.request', $conversation))
            ->assertForbidden();
        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversations.webrtc.answer', $conversation), ['sdp' => str_repeat('v=0 ', 10), 'type' => 'answer'])
            ->assertForbidden();
        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversations.webrtc.ice', $conversation), ['candidate' => ['candidate' => 'x']])
            ->assertForbidden();
        $this->actingAs($agent)
            ->getJson(route('manager.helpdesk.conversations.livestream.history', $conversation))
            ->assertForbidden();
    }

    public function test_manager_can_use_history_and_signalling(): void
    {
        $conversation = Conversation::factory()->create(['status_id' => $this->openStatus->id]);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.livestream.history', $conversation))
            ->assertOk();
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.webrtc.end', $conversation))
            ->assertOk();
    }
}

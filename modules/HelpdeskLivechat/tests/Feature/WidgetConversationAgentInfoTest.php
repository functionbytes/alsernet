<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * The widget conversation payload exposes the assigned agent's display data
 * (name + avatar) — and nothing sensitive (no email, phone or roles).
 */
class WidgetConversationAgentInfoTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private Conversation $conversation;

    private string $token = 'pubsub_agent_info_token_value';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedOpenConversationStatus();

        $this->customer = Customer::factory()->create();

        $this->conversation = Conversation::factory()->create([
            'customer_id' => $this->customer->id,
            'metadata' => ['widget_pubsub_token' => $this->token],
        ]);
    }

    public function test_agent_is_null_when_conversation_is_unassigned(): void
    {
        $this->getJson(
            route('helpdesk-livechat.widget.conversation.show', $this->conversation->id),
            ['X-Conversation-Token' => $this->token]
        )
            ->assertOk()
            ->assertJsonPath('data.agent', null);
    }

    public function test_agent_exposes_only_name_and_avatar_when_assigned(): void
    {
        $agent = User::factory()->create([
            'firstname' => 'Laura',
            'lastname' => 'García',
        ]);

        $this->conversation->update(['assignee_id' => $agent->id]);

        $response = $this->getJson(
            route('helpdesk-livechat.widget.conversation.show', $this->conversation->id),
            ['X-Conversation-Token' => $this->token]
        );

        $response->assertOk()
            ->assertJsonPath('data.agent.id', $agent->id)
            ->assertJsonPath('data.agent.name', 'Laura García');

        $payload = $response->json('data.agent');

        $this->assertArrayHasKey('avatar', $payload);
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertArrayNotHasKey('phone', $payload);
    }
}

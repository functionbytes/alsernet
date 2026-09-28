<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Tests\TestCase;

/**
 * Regresión: a diferencia de handleNodeTimeout() (ChatFlowEngine), este
 * comando terminaba la sesión sin liberar la conversación del bot —
 * metadata.handled_by_bot se quedaba en true para siempre y, si el visitante
 * volvía a escribir más tarde, el mensaje no aparecía en la bandeja del
 * agente (Conversation::scopeWithoutActiveBot).
 */
class ExpireInactiveSessionsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_expired_session_releases_the_conversation_from_the_bot(): void
    {
        $conversation = Conversation::factory()->create([
            'metadata' => ['handled_by_bot' => true],
        ]);

        $session = ChatFlowSession::create([
            'chat_flow_id' => 1,
            'conversation_id' => $conversation->id,
            'status' => 'active',
            'trigger_type' => 'keyword',
            'started_at' => now()->subHour(),
            'last_customer_reply_at' => now()->subHour(),
        ]);

        $this->artisan('chatflow:expire-sessions', ['--minutes' => 30])->assertSuccessful();

        $this->assertDatabaseHas('helpdesk_chat_flow_sessions', [
            'id' => $session->id,
            'status' => 'abandoned',
        ], 'helpdesk');

        $conversation->refresh();
        $this->assertFalse($conversation->metadata['handled_by_bot'] ?? null);
    }
}

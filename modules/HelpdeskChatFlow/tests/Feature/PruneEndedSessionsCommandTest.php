<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskChatFlow\Models\ChatFlowExecution;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Tests\TestCase;

class PruneEndedSessionsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_completed_session_older_than_retention_is_deleted_with_its_executions(): void
    {
        config()->set('helpdeskchatflow.session_retention_days', 90);

        $session = $this->makeSession('completed', endedAt: now()->subDays(100));
        $execution = $this->makeExecution($session->id);

        $this->artisan('chatflow:prune-sessions')->assertSuccessful();

        $this->assertDatabaseMissing('helpdesk_chat_flow_sessions', ['id' => $session->id], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_chat_flow_executions', ['id' => $execution->id], 'helpdesk');
    }

    public function test_completed_session_within_retention_is_kept(): void
    {
        config()->set('helpdeskchatflow.session_retention_days', 90);

        $session = $this->makeSession('completed', endedAt: now()->subDays(10));

        $this->artisan('chatflow:prune-sessions')->assertSuccessful();

        $this->assertDatabaseHas('helpdesk_chat_flow_sessions', ['id' => $session->id], 'helpdesk');
    }

    public function test_active_session_with_old_ended_at_is_kept(): void
    {
        config()->set('helpdeskchatflow.session_retention_days', 90);

        $session = $this->makeSession('active', endedAt: now()->subDays(100));

        $this->artisan('chatflow:prune-sessions')->assertSuccessful();

        $this->assertDatabaseHas('helpdesk_chat_flow_sessions', ['id' => $session->id], 'helpdesk');
    }

    private function makeSession(string $status, ?Carbon $endedAt): ChatFlowSession
    {
        return ChatFlowSession::create([
            'chat_flow_id' => 1,
            'conversation_id' => 1,
            'status' => $status,
            'trigger_type' => 'keyword',
            'started_at' => now()->subDays(101),
            'ended_at' => $endedAt,
        ]);
    }

    private function makeExecution(int $sessionId): ChatFlowExecution
    {
        return ChatFlowExecution::create([
            'session_id' => $sessionId,
            'node_id' => 'n1',
            'node_type' => 'start',
            'status' => 'success',
            'executed_at' => now()->subDays(101),
        ]);
    }
}

<?php

namespace Modules\HelpdeskAgents\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Services\AiAgentFlowEngine;

class StartAiAgentSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public array $backoff = [60, 180, 300];

    public function __construct(
        public AiAgent $agent,
        public Conversation $conversation,
        public ?Customer $customer,
        public string $initialMessage = ''
    ) {
        $this->onQueue('helpdesk-ai');
    }

    public function handle(AiAgentFlowEngine $engine): void
    {
        $session = $engine->startSession(
            $this->agent,
            $this->conversation,
            $this->customer,
            $this->initialMessage
        );

        if ($session && $session->status === 'active') {
            Log::info('AI agent session started', [
                'session_id' => $session->id,
                'agent_id' => $this->agent->id,
                'conversation_id' => $this->conversation->id,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('StartAiAgentSessionJob failed', [
            'agent_id' => $this->agent->id,
            'conversation_id' => $this->conversation->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

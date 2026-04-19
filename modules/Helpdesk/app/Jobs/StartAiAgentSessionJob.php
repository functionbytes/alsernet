<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\AiAgent;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\AiAgentFlowEngine;

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
        try {
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
        } catch (\Throwable $e) {
            Log::error('Failed to start AI agent session', [
                'agent_id' => $this->agent->id,
                'conversation_id' => $this->conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

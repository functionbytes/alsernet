<?php

namespace Modules\HelpdeskChatFlow\Console\Commands;

use Illuminate\Console\Command;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowOutboundService;

/**
 * Launches a proactive (outbound) flow on one or more conversations — e.g. an
 * abandoned-cart nudge or appointment reminder triggered by the scheduler or a
 * back-office action.
 */
class LaunchOutboundFlowCommand extends Command
{
    protected $signature = 'chatflow:outbound
                            {flow : ChatFlow id or uid}
                            {--conversation=* : Conversation id(s) to launch on}
                            {--context= : JSON object of seed context values}';

    protected $description = 'Launch a proactive outbound chat flow on the given conversation(s)';

    public function handle(ChatFlowOutboundService $outbound): int
    {
        $flow = ChatFlow::query()
            ->where('id', $this->argument('flow'))
            ->orWhere('uid', $this->argument('flow'))
            ->first();

        if (! $flow) {
            $this->error("Flow '{$this->argument('flow')}' not found.");

            return self::FAILURE;
        }

        if (! $flow->isActive()) {
            $this->error("Flow '{$flow->name}' is not active.");

            return self::FAILURE;
        }

        $context = $this->parseContext();
        if ($context === null) {
            $this->error('Invalid --context JSON.');

            return self::FAILURE;
        }

        $conversationIds = (array) $this->option('conversation');
        if ($conversationIds === []) {
            $this->error('Provide at least one --conversation id.');

            return self::FAILURE;
        }

        $launched = 0;

        foreach ($conversationIds as $id) {
            $conversation = Conversation::on('helpdesk')->find($id);

            if (! $conversation) {
                $this->warn("Conversation {$id} not found, skipping.");

                continue;
            }

            $session = $outbound->launch($flow, $conversation, $context);

            if ($session) {
                $launched++;
                $this->line("✓ Launched on conversation {$id} (session {$session->id}).");

                continue;
            }

            $this->warn("Conversation {$id} already has an active session, skipping.");
        }

        $this->info("Outbound flow '{$flow->name}' launched on {$launched} conversation(s).");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseContext(): ?array
    {
        $raw = $this->option('context');

        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}

<?php

namespace Modules\HelpdeskChatFlow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;

/**
 * Runs the chat-flow work for a single inbound customer message off the request
 * thread. Dispatched by ConversationItemObserver so the channel webhook returns
 * immediately instead of blocking while a node calls OpenAI (ai_response ~30s,
 * ai_agent ~40s, intent classification ~15s), the ERP/PS order lookup or an
 * arbitrary http_request.
 *
 * Two modes:
 *  - MODE_PROCESS: an active session exists → feed the reply (text + attachments)
 *    to the waiting node via the engine.
 *  - MODE_TRIGGER: no active session → start the conversation_start flow, but only
 *    when this is the conversation's first inbound customer message.
 *
 * The mode supplied at dispatch is only a hint: it is re-resolved at execution
 * time so the job self-corrects if the session state changed in between (e.g. a
 * previously queued trigger job started the session while this message waited).
 * WithoutOverlapping keyed by conversation keeps messages of the same
 * conversation strictly ordered (FIFO) across workers.
 */
class ExecuteChatFlowNodeJob implements ShouldQueue
{
    use Queueable;

    public const MODE_PROCESS = 'process';

    public const MODE_TRIGGER = 'trigger';

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 10;

    public function __construct(
        private readonly int $conversationId,
        private readonly int $itemId,
        private readonly string $mode = self::MODE_PROCESS,
    ) {
        $this->onQueue('chatflow');
    }

    /**
     * Serialize execution per conversation so two inbound messages of the same
     * conversation never run in parallel, preserving FIFO order between workers.
     * The lock outlives a normal run (expireAfter > timeout) so it is only ever
     * released by completion or a dead worker, never mid-execution.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('chatflow-conversation:'.$this->conversationId))
                ->releaseAfter(30)
                ->expireAfter(180),
        ];
    }

    public function handle(ChatFlowEngine $engine): void
    {
        $conversation = Conversation::on('helpdesk')->find($this->conversationId);

        if (! $conversation) {
            return;
        }

        $item = ConversationItem::on('helpdesk')->find($this->itemId);

        if (! $item) {
            return;
        }

        $session = $engine->getActiveSession($conversation);

        // An active session always wins, regardless of the dispatched mode: a
        // trigger job may find the session already started by an earlier queued
        // message and should then process this reply instead of dropping it.
        if ($session) {
            if (! $session->isActive()) {
                return;
            }

            $engine->processMessage($session, $item->body ?? '', $item->attachment_urls ?? []);

            return;
        }

        // No active session: only the trigger mode may start a flow. A process-mode
        // job whose session has since ended exits cleanly rather than re-triggering.
        if ($this->mode !== self::MODE_TRIGGER) {
            return;
        }

        // Start the flow only for the conversation's first inbound customer message
        // (mirrors the original observer gate) so a mid-conversation reply after a
        // finished flow does not silently relaunch the bot.
        $hasPriorCustomerMessage = ConversationItem::on('helpdesk')
            ->where('conversation_id', $this->conversationId)
            ->where('type', 'message')
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->where('id', '<', $this->itemId)
            ->whereJsonDoesntContain('metadata->sent_by_chatflow', true)
            ->exists();

        if ($hasPriorCustomerMessage) {
            return;
        }

        $engine->triggerFor($conversation, 'conversation_start');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExecuteChatFlowNodeJob failed', [
            'conversation_id' => $this->conversationId,
            'item_id' => $this->itemId,
            'mode' => $this->mode,
            'error' => $exception->getMessage(),
        ]);
    }
}

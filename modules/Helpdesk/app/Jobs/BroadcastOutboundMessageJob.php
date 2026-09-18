<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\InboxItemChanged;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

/**
 * Broadcasts an agent-sent message to other agents (and the widget, when
 * applicable) off the HTTP request thread.
 *
 * The sending agent already sees their own message instantly via optimistic
 * UI in the browser — these broadcasts only matter for OTHER viewers, so
 * making the agent's request wait on them (~150ms: two Reverb round-trips +
 * the inbox-changed events) before confirming "sent" was pure latency with
 * no benefit to the person who just clicked send.
 */
class BroadcastOutboundMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 30;

    public int $backoff = 5;

    public function __construct(
        private readonly int $conversationId,
        private readonly int $itemId,
    ) {
        $this->onQueue('helpdesk-webhooks');
    }

    public function handle(): void
    {
        $item = ConversationItem::with(['user', 'author'])->find($this->itemId);
        $conversation = $item?->conversation ?? Conversation::find($this->conversationId);

        if (! $item || ! $conversation) {
            return;
        }

        broadcast(new ConversationMessageCreated($item));

        if (! $item->is_internal) {
            broadcast(new MessageReceived($conversation, $item));
        }

        $userIds = array_filter(array_unique([
            $conversation->assignee_id,
            $item->user_id,
        ]));

        foreach ($userIds as $userId) {
            event(new InboxItemChanged($conversation->id, $userId, 'message_added'));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('BroadcastOutboundMessageJob failed', [
            'conversation_id' => $this->conversationId,
            'item_id' => $this->itemId,
            'error' => $exception->getMessage(),
        ]);
    }
}

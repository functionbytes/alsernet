<?php

namespace Modules\HelpdeskChatFlow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskChatFlow\Services\BotMessageDispatcher;

/**
 * Delivers a bot-authored conversation item to the customer's external channel
 * (WhatsApp/Messenger/Instagram). Dispatched by ConversationItemObserver when a
 * ChatFlow message is created. Web conversations are skipped by the dispatcher.
 */
class DeliverBotMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        private readonly int $itemId,
        private readonly int $conversationId,
    ) {
        $this->onQueue('chatflow');
    }

    public function handle(BotMessageDispatcher $dispatcher): void
    {
        $conversation = Conversation::on('helpdesk')->find($this->conversationId);

        if (! $conversation) {
            return;
        }

        $item = ConversationItem::on('helpdesk')->find($this->itemId);

        if (! $item) {
            return;
        }

        $dispatcher->deliver($conversation, $item);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DeliverBotMessageJob failed', [
            'item_id' => $this->itemId,
            'conversation_id' => $this->conversationId,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace Modules\HelpdeskChatFlow\Observers;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskChatFlow\Jobs\DeliverBotMessageJob;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;

class ConversationItemObserver
{
    public function __construct(private readonly ChatFlowEngine $engine) {}

    public function created(ConversationItem $item): void
    {
        if ($item->type !== 'message') {
            return;
        }

        if ($item->is_internal) {
            return;
        }

        // Outbound bot message: deliver it to the customer's external channel
        // (WhatsApp/Messenger/Instagram). Web is already covered by broadcast.
        if ($item->metadata['sent_by_chatflow'] ?? false) {
            DeliverBotMessageJob::dispatch($item->id, $item->conversation_id);

            return;
        }

        // Only inbound customer messages (user_id null = not from agent)
        if ($item->user_id !== null) {
            return;
        }

        try {
            $conversation = Conversation::on('helpdesk')->find($item->conversation_id);

            if (! $conversation) {
                return;
            }

            $session = $this->engine->getActiveSession($conversation);

            if ($session) {
                $attachments = $item->attachment_urls ?? [];
                $this->engine->processMessage($session, $item->body ?? '', $attachments);

                return;
            }

            $hasPriorCustomerMessage = ConversationItem::on('helpdesk')
                ->where('conversation_id', $item->conversation_id)
                ->where('type', 'message')
                ->where('is_internal', false)
                ->whereNull('user_id')
                ->where('id', '<', $item->id)
                ->whereJsonDoesntContain('metadata->sent_by_chatflow', true)
                ->exists();

            if (! $hasPriorCustomerMessage) {
                $this->engine->triggerFor($conversation, 'conversation_start');
            }
        } catch (\Throwable $e) {
            Log::error('ConversationItemObserver chatflow error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

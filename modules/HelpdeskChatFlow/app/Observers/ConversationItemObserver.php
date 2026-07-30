<?php

namespace Modules\HelpdeskChatFlow\Observers;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskChatFlow\Jobs\DeliverBotMessageJob;
use Modules\HelpdeskChatFlow\Jobs\ExecuteChatFlowNodeJob;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;

class ConversationItemObserver
{
    public function __construct(private readonly ChatFlowEngine $engine) {}

    public function created(ConversationItem $item): void
    {
        // Admin kill switch (Settings → Integraciones): when ChatFlow is toggled
        // off, no chat flow should trigger nor deliver, inbound or outbound.
        if (! helpdesk_chatflow_enabled()) {
            return;
        }

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

        // Cheap gate: this observer is global to the whole helpdesk. When no chat
        // flow is active there can be neither a running session nor a trigger, so
        // bail out before spending 2-3 queries per inbound message of every inbox.
        if (! ChatFlow::hasActiveFlowsCached()) {
            return;
        }

        try {
            $conversation = Conversation::on('helpdesk')->find($item->conversation_id);

            if (! $conversation) {
                return;
            }

            // Offload the (potentially long-running: OpenAI, ERP/PS, http_request)
            // flow work to the queue so the channel webhook returns immediately
            // instead of blocking for tens of seconds. WithoutOverlapping on the job
            // (keyed by conversation_id) preserves FIFO order between workers.
            //
            // The mode is a hint re-resolved at execution time: an active session
            // means "process this reply", otherwise "trigger the start flow". The
            // job self-corrects if the session state changed before it runs.
            $mode = $this->engine->getActiveSession($conversation)
                ? ExecuteChatFlowNodeJob::MODE_PROCESS
                : ExecuteChatFlowNodeJob::MODE_TRIGGER;

            ExecuteChatFlowNodeJob::dispatch($item->conversation_id, $item->id, $mode);
        } catch (\Throwable $e) {
            Log::error('ConversationItemObserver chatflow dispatch error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Helpdesk\Events\ConversationUserTyping;
use Modules\Helpdesk\Http\Requests\BroadcastTypingRequest;
use Modules\Helpdesk\Jobs\SendSenderActionJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationRead;
use Modules\Helpdesk\Services\Conversations\ConversationInboxMetricsService;
use Modules\Helpdesk\Services\OutboundMessageService;

class ConversationMessagesController extends Controller
{
    public function __construct(
        private readonly OutboundMessageService $outbound,
        private readonly ConversationInboxMetricsService $inboxMetrics,
    ) {}

    /**
     * Mark all customer messages in a conversation as read AND notify the
     * customer's external channel (Facebook/Instagram seen indicator).
     */
    public function markConversationRead(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $userId = auth()->id();

        // Mark the whole conversation as read for this user (idempotent).
        // The reads table is keyed by (conversation_id, user_id), not per-item.
        ConversationRead::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $userId],
            ['read_at' => now()],
        );

        // Otherwise the sidebar's "Sin leer" badge keeps showing the stale
        // pre-read count for up to 120s (Cache::flexible TTL) after opening
        // a conversation marks it read.
        $this->inboxMetrics->forgetCountersFor($userId);

        // Send "seen" receipt to the customer via the channel API. This calls
        // out to Meta Graph (or WhatsApp), so it's queued instead of made
        // inline — every conversation open would otherwise pay that request's
        // full latency (up to several seconds) for a receipt the agent never
        // needs to wait on.
        if ($this->outbound->supports($conversation)) {
            $this->dispatchMarkSeen($conversation);
        }

        return response()->json(['success' => true]);
    }

    /**
     * WhatsApp's read receipt is keyed off the customer's latest message id
     * (not their sender id like Facebook/Instagram's mark_seen), so it needs
     * its own lookup before handing off to the queued job.
     */
    private function dispatchMarkSeen(Conversation $conversation): void
    {
        if ($conversation->channel !== 'whatsapp') {
            SendSenderActionJob::dispatch($conversation->channel, $conversation->external_sender_id, 'mark_seen');

            return;
        }

        $latestItem = $conversation->items()
            ->whereNotNull('author_id')
            ->whereNull('user_id')
            ->latest('id')
            ->first(['id', 'external_id']);

        if (filled($latestItem?->external_id)) {
            SendSenderActionJob::dispatch('whatsapp', $latestItem->external_id, 'mark_seen');
        }
    }

    /**
     * Broadcast typing indicator to other agents AND to the customer's channel
     * (Facebook/Instagram show "Typing..." live).
     */
    public function broadcastTyping(BroadcastTypingRequest $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();
        $isTyping = (bool) $validated['is_typing'];

        // Internal typing indicator for other agents watching the same
        // conversation: stays synchronous, the UI needs it immediately.
        broadcast(new ConversationUserTyping(
            $conversation,
            auth()->user(),
            $isTyping,
        ))->toOthers();

        // Forward typing state to the customer's channel (Facebook/Instagram
        // only — WhatsApp has no typing indicator API). Queued for the same
        // reason as markConversationRead: this fires on every keystroke, so
        // an inline Meta Graph call here would mean a burst of blocking HTTP
        // requests per agent per message.
        if ($this->outbound->supports($conversation) && in_array($conversation->channel, ['facebook', 'instagram'], true)) {
            SendSenderActionJob::dispatch(
                $conversation->channel,
                $conversation->external_sender_id,
                $isTyping ? 'typing_on' : 'typing_off',
            );
        }

        return response()->json(['success' => true]);
    }
}

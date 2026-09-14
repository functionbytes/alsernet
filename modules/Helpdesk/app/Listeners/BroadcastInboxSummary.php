<?php

namespace Modules\Helpdesk\Listeners;

use Modules\Helpdesk\Events\ConversationInboxItemCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;

/**
 * SEC-01: fires the lightweight, per-inbox-authorized companion broadcast
 * for the sidebar every time ConversationMessageCreated is dispatched — see
 * ConversationInboxItemCreated for what it sends and why.
 *
 * Deliberately NOT a queued listener (no ShouldQueue): ConversationMessageCreated
 * implements ShouldBroadcastNow, so its own broadcast happens synchronously,
 * inline, at dispatch time. Queueing this companion broadcast would delay the
 * sidebar update behind the (best-effort) queue worker, which would be a
 * regression from today's "instant" behaviour once real-time broadcasting
 * (Reverb) is turned back on.
 */
class BroadcastInboxSummary
{
    public function handle(ConversationMessageCreated $event): void
    {
        broadcast(new ConversationInboxItemCreated($event->item, $event->isNewConversation));
    }
}

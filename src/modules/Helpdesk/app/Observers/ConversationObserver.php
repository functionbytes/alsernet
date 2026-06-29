<?php

namespace Modules\Helpdesk\Observers;

use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Models\Conversation;

class ConversationObserver
{
    public function updated(Conversation $conversation): void
    {
        if ($conversation->wasChanged(['priority', 'group_id', 'assignee_id', 'status_id'])) {
            ConversationUpdated::dispatch($conversation);
        }
    }
}

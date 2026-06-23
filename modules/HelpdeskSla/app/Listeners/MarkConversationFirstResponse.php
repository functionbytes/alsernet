<?php

namespace Modules\HelpdeskSla\Listeners;

use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\HelpdeskSla\Services\ConversationSlaService;

/**
 * Stops the first-response SLA clock when an agent sends the first reply.
 * Customer (incoming) messages and internal notes are ignored. Idempotent: only
 * sets first_response_at when it is still null.
 */
class MarkConversationFirstResponse
{
    public function __construct(
        private readonly ConversationSlaService $slaService,
    ) {}

    public function handle(ConversationMessageCreated $event): void
    {
        $item = $event->item;

        if ($item->type !== 'message' || $item->is_internal) {
            return;
        }

        // Agent replies carry a user_id; incoming customer messages do not.
        if (empty($item->user_id)) {
            return;
        }

        $this->slaService->markFirstResponse((int) $item->conversation_id);
    }
}

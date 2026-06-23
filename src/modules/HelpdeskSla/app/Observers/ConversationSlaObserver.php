<?php

namespace Modules\HelpdeskSla\Observers;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskSla\Services\ConversationSlaService;

/**
 * Initializes SLA tracking for every conversation regardless of the creation
 * path (webhook, API, manual, simulator) and finalizes it on close. Uses quiet
 * updates inside the service to avoid event recursion.
 */
class ConversationSlaObserver
{
    public function __construct(
        private readonly ConversationSlaService $slaService,
    ) {}

    public function created(Conversation $conversation): void
    {
        $this->slaService->initialize($conversation);
    }

    public function updated(Conversation $conversation): void
    {
        if ($conversation->wasChanged('closed_at') && $conversation->closed_at !== null) {
            $this->slaService->finalize($conversation);

            return;
        }

        if ($conversation->wasChanged('snoozed_until')) {
            $snoozed = $conversation->snoozed_until !== null && $conversation->snoozed_until->isFuture();

            $snoozed
                ? $this->slaService->pauseSla($conversation)
                : $this->slaService->resumeSla($conversation);

            return;
        }

        if ($conversation->wasChanged('priority')) {
            $this->slaService->recalculate($conversation);
        }
    }
}

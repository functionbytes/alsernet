<?php

namespace Modules\HelpdeskChatFlow\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Fired when a customer answers a CSAT node. Lets listeners (agent notification,
 * CRM, reporting) react to satisfaction scores instead of leaving them buried in
 * the session context.
 */
class ChatFlowCsatRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ChatFlowSession $session,
        public readonly int $score,
    ) {}
}

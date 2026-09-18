<?php

namespace Modules\HelpdeskChatFlow\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

class ChatFlowCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ChatFlowSession $session
    ) {}
}

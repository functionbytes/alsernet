<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationTag;

class ConversationTagAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly ConversationTag $tag,
        public readonly ?int $byUserId = null,
    ) {}
}

<?php

namespace Modules\HelpdeskAiPrompts\Observers;

use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskAiPrompts\Services\PromptRunRecorder;

class ConversationItemAiCaseObserver
{
    public function __construct(private readonly PromptRunRecorder $recorder) {}

    public function created(ConversationItem $item): void
    {
        $this->recorder->linkConversationItem($item);
    }
}

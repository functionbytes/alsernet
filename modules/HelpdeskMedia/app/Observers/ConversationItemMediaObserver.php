<?php

namespace Modules\HelpdeskMedia\Observers;

use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskMedia\Jobs\ProcessConversationMediaJob;
use Modules\HelpdeskMedia\Services\ConversationMediaProcessor;

class ConversationItemMediaObserver
{
    public function created(ConversationItem $item): void
    {
        $this->dispatchIfPending($item);
    }

    public function updated(ConversationItem $item): void
    {
        if ($item->wasChanged('attachment_urls')) {
            $this->dispatchIfPending($item);
        }
    }

    private function dispatchIfPending(ConversationItem $item): void
    {
        if (! config('helpdeskmedia.enabled') || ! config('helpdeskmedia.sources.conversations')) {
            return;
        }

        if ($item->type === 'activity' || ! ConversationMediaProcessor::hasPending($item)) {
            return;
        }

        ProcessConversationMediaJob::dispatch($item->id);
    }
}

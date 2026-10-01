<?php

namespace Modules\HelpdeskMedia\Observers;

use Modules\HelpdeskMedia\Jobs\ProcessTicketAttachmentJob;
use Modules\HelpdeskMedia\Services\TicketAttachmentProcessor;
use Modules\HelpdeskTickets\Models\TicketAttachment;

class TicketAttachmentMediaObserver
{
    public function created(TicketAttachment $attachment): void
    {
        if (! config('helpdeskmedia.enabled') || ! config('helpdeskmedia.sources.tickets')) {
            return;
        }

        if (! TicketAttachmentProcessor::columnsReady()) {
            return;
        }

        ProcessTicketAttachmentJob::dispatch($attachment->id);
    }
}

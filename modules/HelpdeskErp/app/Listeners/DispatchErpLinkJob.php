<?php

namespace Modules\HelpdeskErp\Listeners;

use Modules\Helpdesk\Events\ConversationCreated;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;

class DispatchErpLinkJob
{
    public function handle(ConversationCreated $event): void
    {
        if (! helpdesk_erp_enabled()) {
            return;
        }

        $customerId = $event->conversation->customer_id;

        if ($customerId !== null) {
            LinkCustomerToErpJob::dispatch($customerId);
        }
    }
}

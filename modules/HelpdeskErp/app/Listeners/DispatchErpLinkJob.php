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
            // El origen viaja con el trabajo para que CustomerErpResolved
            // pueda enrutar esta conversación en concreto.
            LinkCustomerToErpJob::dispatch($customerId, 'conversation', $event->conversation->id);
        }
    }
}

<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Ecommerce\Events\AccountDeleting;
use Modules\Ecommerce\Jobs\CustomerDeleteAccountJob;

class HandleCustomerAccountDeletion implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(AccountDeleting $event): void
    {
        // Schedule the deletion job with a 7-day delay so the customer can
        // cancel the request via /api/v1/me/cancel-deletion (future endpoint)
        // or contacting support.
        CustomerDeleteAccountJob::dispatch($event->customer->id)
            ->delay(now()->addDays(7));
    }
}

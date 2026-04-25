<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\CustomerEmailVerified;

class SendMailsAfterCustomerRegistered
{
    public function handle(CustomerEmailVerified $event): void
    {
        // TODO: Send registration confirmation
    }
}

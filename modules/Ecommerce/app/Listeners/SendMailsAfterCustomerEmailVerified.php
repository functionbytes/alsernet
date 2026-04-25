<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\CustomerEmailVerified;

class SendMailsAfterCustomerEmailVerified
{
    public function handle(CustomerEmailVerified $event): void
    {
        // TODO: Send welcome email
    }
}

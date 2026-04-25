<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderCompleted;

class SendProductReviewsMailAfterOrderCompleted
{
    public function handle(OrderCompleted $event): void
    {
        // Send review request email to customer
    }
}

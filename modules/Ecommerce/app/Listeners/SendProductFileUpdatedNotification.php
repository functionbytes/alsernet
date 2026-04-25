<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\ProductFileUpdated;

class SendProductFileUpdatedNotification
{
    public function handle(ProductFileUpdated $event): void
    {
        // Notify customers who purchased this product about file update
    }
}

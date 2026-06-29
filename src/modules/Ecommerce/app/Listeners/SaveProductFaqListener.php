<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\ProductFileUpdated;

class SaveProductFaqListener
{
    public function handle(ProductFileUpdated $event): void
    {
        // Save product FAQ data
    }
}

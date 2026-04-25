<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\ProductVariationCreated;

class UpdateProductVariationInfo
{
    public function handle(ProductVariationCreated $event): void
    {
        // TODO: Sync variation data with parent product
    }
}

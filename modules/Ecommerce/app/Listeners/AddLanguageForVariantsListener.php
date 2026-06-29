<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\ProductVariationCreated;

class AddLanguageForVariantsListener
{
    public function handle(ProductVariationCreated $event): void
    {
        // Sync language settings for the new variation
    }
}

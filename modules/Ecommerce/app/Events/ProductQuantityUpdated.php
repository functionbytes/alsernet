<?php

namespace Modules\Ecommerce\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Product;

class ProductQuantityUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Product $product,
        public int $oldQuantity,
        public int $newQuantity
    ) {}
}

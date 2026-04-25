<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\ProductViewed;
use Modules\Ecommerce\Models\ProductView;

class UpdateProductView
{
    public function handle(ProductViewed $event): void
    {
        ProductView::query()->updateOrCreate(
            ['product_id' => $event->product->id, 'date' => now()->toDateString()],
            ['views' => \DB::raw('views + 1')]
        );
    }
}

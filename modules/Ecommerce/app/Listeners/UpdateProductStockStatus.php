<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderPlaced;
use Modules\Ecommerce\Models\Product;

class UpdateProductStockStatus
{
    public function handle(OrderPlaced $event): void
    {
        foreach ($event->order->items as $item) {
            $product = Product::query()->find($item->product_id);
            if ($product && $product->with_storehouse_management) {
                $product->decrement('quantity', $item->qty);
                if ($product->quantity <= 0) {
                    $product->update(['stock_status' => 'out_of_stock']);
                }
            }
        }
    }
}

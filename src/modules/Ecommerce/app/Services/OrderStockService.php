<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Order;

class OrderStockService
{
    public function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = $item->product;

            if ($product && $product->with_storehouse_management) {
                $product->increment('quantity', $item->qty);
                if ($product->stock_status === 'out_of_stock' && $product->quantity > 0) {
                    $product->update(['stock_status' => 'in_stock']);
                }
            }
        }
    }
}

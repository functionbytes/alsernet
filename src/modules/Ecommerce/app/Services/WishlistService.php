<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Wishlist;

class WishlistService
{
    public function add(Customer $customer, Product $product): Wishlist
    {
        return Wishlist::query()->firstOrCreate([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
        ]);
    }

    public function remove(Customer $customer, Product $product): void
    {
        Wishlist::query()
            ->where('customer_id', $customer->id)
            ->where('product_id', $product->id)
            ->delete();
    }

    public function getWishlist(Customer $customer)
    {
        return Wishlist::query()
            ->where('customer_id', $customer->id)
            ->with('product')
            ->get();
    }
}

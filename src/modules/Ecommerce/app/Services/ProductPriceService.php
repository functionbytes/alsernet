<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;

class ProductPriceService
{
    public function getPrice(Product $product): float
    {
        $price = $product->price;

        if ($product->sale_price && $product->sale_price > 0 && now()->between($product->start_date, $product->end_date)) {
            $price = $product->sale_price;
        }

        $flashSalePrice = app(FlashSaleService::class)->getActiveFlashSales()
            ->flatMap->products
            ->firstWhere('product_id', $product->id);

        if ($flashSalePrice && $flashSalePrice->price > 0) {
            $price = $flashSalePrice->price;
        }

        return (float) $price;
    }

    public function getPriceWithTax(Product $product, ?float $taxRate = null): float
    {
        $price = $this->getPrice($product);

        if ($taxRate) {
            $price += $price * ($taxRate / 100);
        }

        return $price;
    }
}

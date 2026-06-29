<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;

class ProductPriceHandlerService
{
    public function __construct(
        protected ProductPriceService $priceService,
        protected ProductDiscountPriceService $discountPriceService,
        protected ProductFlashSalePriceService $flashSalePriceService,
        protected ProductSalePriceService $salePriceService,
    ) {}

    public function getFinalPrice(Product $product): float
    {
        $salePrice = $this->salePriceService->getSalePrice($product);
        if ($salePrice) {
            return $salePrice;
        }

        $flashSalePrice = $this->flashSalePriceService->getPrice($product);
        if ($flashSalePrice) {
            return $flashSalePrice;
        }

        return $this->priceService->getPrice($product);
    }
}

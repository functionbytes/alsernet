<?php

namespace Modules\Ecommerce\Observers;

use Modules\Ecommerce\Jobs\ProcessProductImageJob;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Services\ImageProcessingService;

class ProductImageObserver
{
    public function saved(Product $product): void
    {
        if (! $product->wasChanged('featured_image') || ! $product->featured_image) {
            return;
        }

        $oldImage = $product->getOriginal('featured_image');

        if ($oldImage) {
            app(ImageProcessingService::class)->deleteProductImageVariants($oldImage);
        }

        ProcessProductImageJob::dispatch($product->featured_image);
    }

    public function deleted(Product $product): void
    {
        if (! $product->featured_image) {
            return;
        }

        app(ImageProcessingService::class)->deleteProductImageVariants($product->featured_image);
    }
}

<?php

namespace Modules\Ecommerce\Supports;

class ProductionHelper
{
    public static function getProductImageUrl(?string $image): string
    {
        if (! $image) {
            return asset('images/placeholder.png');
        }

        return str_starts_with($image, 'http') ? $image : asset('storage/'.$image);
    }
}

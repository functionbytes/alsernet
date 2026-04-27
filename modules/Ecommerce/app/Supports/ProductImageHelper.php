<?php

namespace Modules\Ecommerce\Supports;

use Illuminate\Support\Facades\Storage;

class ProductImageHelper
{
    public static function url(?string $imagePath, string $size = 'large', string $format = 'webp'): string
    {
        if (! $imagePath) {
            return asset('modules/ecommerce/images/product-placeholder.svg');
        }

        if (str_starts_with($imagePath, 'http')) {
            return $imagePath;
        }

        $pathInfo = pathinfo($imagePath);
        $variantPath = "{$pathInfo['dirname']}/{$pathInfo['filename']}-{$size}.{$format}";

        if (Storage::disk('public')->exists($variantPath)) {
            return asset('storage/'.$variantPath);
        }

        return asset('storage/'.$imagePath);
    }

    public static function srcset(?string $imagePath, string $format = 'webp'): string
    {
        if (! $imagePath || str_starts_with($imagePath, 'http')) {
            return '';
        }

        return implode(', ', [
            self::url($imagePath, 'thumb', $format).' 200w',
            self::url($imagePath, 'medium', $format).' 600w',
            self::url($imagePath, 'large', $format).' 1200w',
        ]);
    }
}

<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class ImageProcessingService
{
    private const SIZES = [
        'thumb' => 200,
        'medium' => 600,
        'large' => 1200,
    ];

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new GdDriver);
    }

    public function processProductImage(string $originalPath): array
    {
        if (! Storage::disk('public')->exists($originalPath)) {
            Log::warning('ImageProcessingService: file not found', ['path' => $originalPath]);

            return [];
        }

        $generated = [];
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $absolutePath = Storage::disk('public')->path($originalPath);

        foreach (self::SIZES as $sizeName => $width) {
            $image = $this->manager->read($absolutePath);
            $image->scaleDown(width: $width);

            $jpgRelative = "{$directory}/{$filename}-{$sizeName}.jpg";
            $webpRelative = "{$directory}/{$filename}-{$sizeName}.webp";

            $image->toJpeg(quality: 85)->save(Storage::disk('public')->path($jpgRelative));
            $image->toWebp(quality: 85)->save(Storage::disk('public')->path($webpRelative));

            $generated[$sizeName] = [
                'jpg' => $jpgRelative,
                'webp' => $webpRelative,
            ];
        }

        return $generated;
    }

    public function deleteProductImageVariants(string $originalPath): void
    {
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];

        foreach (self::SIZES as $sizeName => $_width) {
            Storage::disk('public')->delete("{$directory}/{$filename}-{$sizeName}.jpg");
            Storage::disk('public')->delete("{$directory}/{$filename}-{$sizeName}.webp");
        }
    }
}

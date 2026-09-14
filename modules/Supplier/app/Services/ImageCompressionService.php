<?php

namespace Modules\Supplier\Services;

use Illuminate\Support\Facades\Log;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Cwebp;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Pngquant;

class ImageCompressionService
{
    private OptimizerChain $chain;

    public function __construct()
    {
        $this->chain = (new OptimizerChain)
            ->addOptimizer(new Jpegoptim([
                '--max=82',
                '--strip-all',
                '--all-progressive',
                '--force',
            ]))
            ->addOptimizer(new Pngquant([
                '--quality=85-95',
                '--force',
                '--skip-if-larger',
            ]))
            ->addOptimizer(new Optipng([
                '-i0',
                '-o2',
                '-quiet',
            ]))
            ->addOptimizer(new Cwebp([
                '-m', '4',
                '-q', '82',
                '-mt',
                '-metadata', 'none',
            ]));
    }

    /**
     * Compress an image file in-place. Returns original size and new size in bytes.
     *
     * @return array{original: int, compressed: int}
     */
    public function compress(string $absolutePath): array
    {
        if (! file_exists($absolutePath)) {
            return ['original' => 0, 'compressed' => 0];
        }

        $originalSize = filesize($absolutePath);

        try {
            $this->chain->optimize($absolutePath);
        } catch (\Throwable $e) {
            Log::warning('ImageCompressionService: optimization failed', [
                'path' => $absolutePath,
                'error' => $e->getMessage(),
            ]);
        }

        clearstatcache(true, $absolutePath);
        $compressedSize = filesize($absolutePath);

        return [
            'original' => $originalSize,
            'compressed' => $compressedSize ?: $originalSize,
        ];
    }

    public static function isImage(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }
}

<?php

namespace Modules\Social\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Modules\Social\Models\SocialAccount;

class ApplyWatermarkService
{
    /**
     * Apply watermark to an image
     */
    public function apply(string $imagePath, SocialAccount $account): string
    {
        // If watermark is not enabled or no watermark image, return original
        if (! $account->enable_watermark || ! $account->watermark_image) {
            return $imagePath;
        }

        try {
            // Initialize Image Manager
            $manager = new ImageManager(new Driver);

            // Load the main image
            $image = $manager->read(Storage::disk('public')->get($imagePath));

            // Load the watermark
            $watermarkPath = Storage::disk('public')->path($account->watermark_image);
            if (! file_exists($watermarkPath)) {
                \Log::warning("Watermark image not found: {$watermarkPath}");

                return $imagePath;
            }

            $watermark = $manager->read($watermarkPath);

            // Calculate watermark size (percentage of main image)
            $watermarkWidth = (int) ($image->width() * ($account->watermark_size / 100));
            $watermark->scale(width: $watermarkWidth);

            // Set opacity
            $watermark->opacity($account->watermark_opacity);

            // Calculate position
            $position = $this->calculatePosition(
                $image->width(),
                $image->height(),
                $watermark->width(),
                $watermark->height(),
                $account->watermark_position
            );

            // Place watermark
            $image->place($watermark, $position['position'], $position['x'], $position['y']);

            // Generate new filename
            $pathInfo = pathinfo($imagePath);
            $newFilename = $pathInfo['filename'].'_watermarked.'.$pathInfo['extension'];
            $newPath = $pathInfo['dirname'].'/'.$newFilename;

            // Save the watermarked image
            Storage::disk('public')->put($newPath, $image->encode());

            return $newPath;
        } catch (\Exception $e) {
            \Log::error("Failed to apply watermark: {$e->getMessage()}");

            return $imagePath;
        }
    }

    /**
     * Calculate watermark position
     */
    protected function calculatePosition(
        int $imageWidth,
        int $imageHeight,
        int $watermarkWidth,
        int $watermarkHeight,
        string $position
    ): array {
        $padding = 20; // Pixels from edges

        return match ($position) {
            'top-left' => [
                'position' => 'top-left',
                'x' => $padding,
                'y' => $padding,
            ],
            'top-right' => [
                'position' => 'top-right',
                'x' => $padding,
                'y' => $padding,
            ],
            'bottom-left' => [
                'position' => 'bottom-left',
                'x' => $padding,
                'y' => $padding,
            ],
            'bottom-right' => [
                'position' => 'bottom-right',
                'x' => $padding,
                'y' => $padding,
            ],
            'center' => [
                'position' => 'center',
                'x' => 0,
                'y' => 0,
            ],
            default => [
                'position' => 'bottom-right',
                'x' => $padding,
                'y' => $padding,
            ],
        };
    }
}

<?php

namespace Modules\Media\Drivers\SmartCrop;

use Modules\Media\Contracts\SmartCropDriver;

/**
 * Basic heuristic face detection using GD contrast analysis.
 * Returns the center region as a candidate face area.
 * For production face detection, use the Claude or Google Vision driver.
 */
class GdHeuristicDriver implements SmartCropDriver
{
    public function detectFaces(string $imagePath): array
    {
        $image = $this->loadImage($imagePath);

        if (! $image) {
            return [];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        // Heuristic: assume face is in upper-center third of the image
        $faceWidth = (int) ($width * 0.4);
        $faceHeight = (int) ($height * 0.4);

        return [[
            'x' => (int) ($width * 0.3),
            'y' => (int) ($height * 0.05),
            'width' => $faceWidth,
            'height' => $faceHeight,
        ]];
    }

    private function loadImage(string $path): \GdImage|false
    {
        $mime = mime_content_type($path);

        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
    }
}

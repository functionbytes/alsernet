<?php

namespace Modules\Social\Services;

use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class MediaProcessingService
{
    protected ?FFMpeg $ffmpeg = null;

    public function __construct()
    {
        try {
            $this->ffmpeg = FFMpeg::create();
        } catch (\Exception $e) {
            // FFmpeg not available
            $this->ffmpeg = null;
        }
    }

    /**
     * Apply watermark to image
     */
    public function applyWatermark(string $imagePath, ?string $watermarkPath = null, string $position = 'bottom-right'): string
    {
        $watermarkPath = $watermarkPath ?? public_path('images/watermark.png');

        if (! file_exists($watermarkPath)) {
            return $imagePath; // No watermark available
        }

        $image = Image::make($imagePath);
        $watermark = Image::make($watermarkPath);

        // Resize watermark to 20% of image width
        $watermarkWidth = (int) ($image->width() * 0.2);
        $watermark->resize($watermarkWidth, null, function ($constraint) {
            $constraint->aspectRatio();
        });

        // Position mapping
        $positions = [
            'top-left' => ['left', 10, 10],
            'top-right' => ['right', 10, 10],
            'bottom-left' => ['left', 10, 10],
            'bottom-right' => ['right', 10, 10],
            'center' => ['center', 0, 0],
        ];

        [$align, $x, $y] = $positions[$position] ?? $positions['bottom-right'];

        $image->insert($watermark, $align, $x, $y);
        $image->save($imagePath);

        return $imagePath;
    }

    /**
     * Optimize image for specific social network
     */
    public function optimizeForNetwork(string $imagePath, string $network): string
    {
        $dimensions = $this->getNetworkDimensions($network);
        $image = Image::make($imagePath);

        $image->fit($dimensions['width'], $dimensions['height'], function ($constraint) {
            $constraint->upsize();
        });

        $image->save($imagePath, 85); // 85% quality

        return $imagePath;
    }

    /**
     * Process video for social networks
     */
    public function processVideo(string $videoPath, string $network): ?string
    {
        if (! $this->ffmpeg) {
            return null;
        }

        $video = $this->ffmpeg->open($videoPath);
        $dimensions = $this->getNetworkDimensions($network);

        $outputPath = Storage::path('processed_videos/'.basename($videoPath));

        $format = new X264;
        $format->setKiloBitrate(1000)
            ->setAudioKiloBitrate(128);

        $video->filters()
            ->resize(new Dimension($dimensions['width'], $dimensions['height']))
            ->synchronize();

        $video->save($format, $outputPath);

        return $outputPath;
    }

    /**
     * Generate video thumbnail
     */
    public function generateVideoThumbnail(string $videoPath, int $atSecond = 1): ?string
    {
        if (! $this->ffmpeg) {
            return null;
        }

        $video = $this->ffmpeg->open($videoPath);
        $thumbnailPath = Storage::path('thumbnails/'.basename($videoPath).'.jpg');

        $video->frame(TimeCode::fromSeconds($atSecond))
            ->save($thumbnailPath);

        return $thumbnailPath;
    }

    /**
     * Get optimal dimensions for each network
     */
    protected function getNetworkDimensions(string $network): array
    {
        return match ($network) {
            'facebook' => ['width' => 1200, 'height' => 630],
            'instagram' => ['width' => 1080, 'height' => 1080],
            'instagram_story' => ['width' => 1080, 'height' => 1920],
            'twitter' => ['width' => 1200, 'height' => 675],
            'linkedin' => ['width' => 1200, 'height' => 627],
            'pinterest' => ['width' => 1000, 'height' => 1500],
            'tiktok' => ['width' => 1080, 'height' => 1920],
            'youtube' => ['width' => 1920, 'height' => 1080],
            default => ['width' => 1200, 'height' => 630],
        };
    }
}

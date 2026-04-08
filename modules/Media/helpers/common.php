<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('media_url')) {
    /**
     * Get the public URL for a media file path.
     */
    function media_url(string $url, ?string $disk = null): string
    {
        $disk = $disk ?? config('media.default_disk', 'media');

        return Storage::disk($disk)->url($url);
    }
}

if (! function_exists('is_media_image')) {
    /**
     * Check if a MIME type corresponds to an image.
     */
    function is_media_image(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }
}

if (! function_exists('media_human_size')) {
    /**
     * Format bytes into a human-readable size string.
     */
    function media_human_size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $bytes;

        foreach ($units as $unit) {
            if ($size < 1024) {
                return round($size, 2).' '.$unit;
            }
            $size /= 1024;
        }

        return round($size, 2).' TB';
    }
}

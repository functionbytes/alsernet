<?php

namespace Modules\Social\Services\Publishers;

use Modules\Social\Models\Post;

abstract class BasePublisher
{
    /**
     * Publish a post to the social network.
     *
     * @return array ['status' => 1, 'id' => '123', 'url' => 'https://...']
     */
    abstract public function publish(Post $post): array;

    /**
     * Validate post before publishing.
     */
    public function validate(Post $post): array
    {
        return [];
    }

    /**
     * Get media URLs from post data.
     */
    protected function getMediaUrls(Post $post): array
    {
        $media = $post->media;

        if (empty($media)) {
            return [];
        }

        // Handle different media formats
        if (is_string($media)) {
            return [$media];
        }

        if (is_array($media)) {
            // Flatten nested arrays
            $urls = [];
            array_walk_recursive($media, function ($item) use (&$urls) {
                if (is_string($item) && filter_var($item, FILTER_VALIDATE_URL)) {
                    $urls[] = $item;
                }
            });

            return $urls;
        }

        return [];
    }

    /**
     * Check if media is an image.
     */
    protected function isImage(string $url): bool
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Check if media is a video.
     */
    protected function isVideo(string $url): bool
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        return in_array($extension, ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm']);
    }

    /**
     * Get full media URL.
     */
    protected function getFullMediaUrl(string $path): string
    {
        // If already a full URL, return as is
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Otherwise, prepend storage URL
        return \Storage::url($path);
    }
}

<?php

namespace Modules\Social\Services\Publishers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Social\Enums\PostType;
use Modules\Social\Models\Post;

class InstagramPublisher extends BasePublisher
{
    /**
     * Facebook Graph API version (Instagram uses Facebook Graph API).
     */
    protected string $graphVersion = 'v21.0';

    /**
     * Publish post to Instagram.
     */
    public function publish(Post $post): array
    {
        $accessToken = decrypt($post->socialAccount->access_token);
        $igAccountId = $post->socialAccount->network_id;

        try {
            return match ($post->type) {
                PostType::IMAGE => $this->publishImage($igAccountId, $accessToken, $post),
                PostType::VIDEO => $this->publishVideo($igAccountId, $accessToken, $post),
                PostType::CAROUSEL => $this->publishCarousel($igAccountId, $accessToken, $post),
                PostType::REELS => $this->publishReels($igAccountId, $accessToken, $post),
                default => throw new Exception("Instagram does not support {$post->type->value} posts"),
            };
        } catch (Exception $e) {
            Log::error("Instagram publish error: {$e->getMessage()}", [
                'post_id' => $post->id,
                'code' => $e->getCode(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate post before publishing.
     */
    public function validate(Post $post): array
    {
        $errors = [];
        $mediaUrls = $this->getMediaUrls($post);

        // Instagram doesn't support text-only or link posts
        if (in_array($post->type, [PostType::TEXT, PostType::LINK])) {
            $errors[] = 'Instagram does not support text-only or link posts.';
        }

        // Reels must have exactly one video
        if ($post->type === PostType::REELS) {
            if (empty($mediaUrls)) {
                $errors[] = 'Reels require exactly one video.';
            } elseif (count($mediaUrls) > 1) {
                $errors[] = 'Reels support only one video.';
            } elseif (! $this->isVideo($mediaUrls[0])) {
                $errors[] = 'Reels must be a video file.';
            }
        }

        // Carousel needs 2-10 items
        if ($post->type === PostType::CAROUSEL) {
            if (count($mediaUrls) < 2) {
                $errors[] = 'Carousel posts require at least 2 images.';
            } elseif (count($mediaUrls) > 10) {
                $errors[] = 'Instagram carousel posts support maximum 10 items.';
            }
        }

        return $errors;
    }

    /**
     * Publish image post.
     */
    protected function publishImage(string $igAccountId, string $accessToken, Post $post): array
    {
        $mediaUrls = $this->getMediaUrls($post);

        if (empty($mediaUrls)) {
            throw new Exception('No media found for image post');
        }

        // Step 1: Create media container
        $containerResponse = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media", [
            'image_url' => $this->getFullMediaUrl($mediaUrls[0]),
            'caption' => $post->content,
            'access_token' => $accessToken,
        ]);

        if ($containerResponse->failed()) {
            throw new Exception($containerResponse->json()['error']['message'] ?? 'Failed to create media container', $containerResponse->json()['error']['code'] ?? 0);
        }

        $containerId = $containerResponse->json()['id'] ?? null;

        if (! $containerId) {
            throw new Exception('Failed to get container ID from Instagram');
        }

        // Step 2: Publish container
        $publishResponse = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media_publish", [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ]);

        if ($publishResponse->failed()) {
            throw new Exception($publishResponse->json()['error']['message'] ?? 'Failed to publish post', $publishResponse->json()['error']['code'] ?? 0);
        }

        $mediaId = $publishResponse->json()['id'] ?? null;

        return [
            'status' => 1,
            'id' => $mediaId,
            'url' => $mediaId ? "https://www.instagram.com/p/{$this->getShortcodeFromId($mediaId)}" : null,
        ];
    }

    /**
     * Publish video post.
     */
    protected function publishVideo(string $igAccountId, string $accessToken, Post $post): array
    {
        $mediaUrls = $this->getMediaUrls($post);

        if (empty($mediaUrls)) {
            throw new Exception('No media found for video post');
        }

        // Step 1: Create video container
        $containerResponse = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media", [
            'media_type' => 'VIDEO',
            'video_url' => $this->getFullMediaUrl($mediaUrls[0]),
            'caption' => $post->content,
            'access_token' => $accessToken,
        ]);

        if ($containerResponse->failed()) {
            throw new Exception($containerResponse->json()['error']['message'] ?? 'Failed to create video container', $containerResponse->json()['error']['code'] ?? 0);
        }

        $containerId = $containerResponse->json()['id'] ?? null;

        if (! $containerId) {
            throw new Exception('Failed to get container ID from Instagram');
        }

        // Step 2: Wait for video processing (poll status)
        $this->waitForVideoProcessing($containerId, $accessToken);

        // Step 3: Publish container
        $publishResponse = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media_publish", [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ]);

        if ($publishResponse->failed()) {
            throw new Exception($publishResponse->json()['error']['message'] ?? 'Failed to publish video', $publishResponse->json()['error']['code'] ?? 0);
        }

        $mediaId = $publishResponse->json()['id'] ?? null;

        return [
            'status' => 1,
            'id' => $mediaId,
            'url' => $mediaId ? "https://www.instagram.com/p/{$this->getShortcodeFromId($mediaId)}" : null,
        ];
    }

    /**
     * Publish carousel post.
     */
    protected function publishCarousel(string $igAccountId, string $accessToken, Post $post): array
    {
        $mediaUrls = $this->getMediaUrls($post);

        if (count($mediaUrls) < 2) {
            throw new Exception('Carousel posts require at least 2 items');
        }

        // Step 1: Create item containers
        $itemContainers = [];
        foreach ($mediaUrls as $mediaUrl) {
            $fullUrl = $this->getFullMediaUrl($mediaUrl);
            $isVideo = $this->isVideo($mediaUrl);

            $containerResponse = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media", [
                'is_carousel_item' => 'true',
                $isVideo ? 'media_type' : 'image_url' => $isVideo ? 'VIDEO' : $fullUrl,
                $isVideo ? 'video_url' : '' => $isVideo ? $fullUrl : '',
                'access_token' => $accessToken,
            ]);

            if ($containerResponse->successful() && isset($containerResponse->json()['id'])) {
                $itemContainers[] = $containerResponse->json()['id'];
            }
        }

        if (empty($itemContainers)) {
            throw new Exception('Failed to create carousel item containers');
        }

        // Step 2: Create carousel container
        $carouselResponse = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media", [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $itemContainers),
            'caption' => $post->content,
            'access_token' => $accessToken,
        ]);

        if ($carouselResponse->failed()) {
            throw new Exception($carouselResponse->json()['error']['message'] ?? 'Failed to create carousel', $carouselResponse->json()['error']['code'] ?? 0);
        }

        $containerId = $carouselResponse->json()['id'] ?? null;

        // Step 3: Publish carousel
        $publishResponse = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media_publish", [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ]);

        if ($publishResponse->failed()) {
            throw new Exception($publishResponse->json()['error']['message'] ?? 'Failed to publish carousel', $publishResponse->json()['error']['code'] ?? 0);
        }

        $mediaId = $publishResponse->json()['id'] ?? null;

        return [
            'status' => 1,
            'id' => $mediaId,
            'url' => $mediaId ? "https://www.instagram.com/p/{$this->getShortcodeFromId($mediaId)}" : null,
        ];
    }

    /**
     * Publish Reels.
     */
    protected function publishReels(string $igAccountId, string $accessToken, Post $post): array
    {
        $mediaUrls = $this->getMediaUrls($post);

        if (empty($mediaUrls)) {
            throw new Exception('No media found for Reels');
        }

        // Step 1: Create Reels container
        $containerResponse = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media", [
            'media_type' => 'REELS',
            'video_url' => $this->getFullMediaUrl($mediaUrls[0]),
            'caption' => $post->content,
            'share_to_feed' => 'true',
            'access_token' => $accessToken,
        ]);

        if ($containerResponse->failed()) {
            throw new Exception($containerResponse->json()['error']['message'] ?? 'Failed to create Reels container', $containerResponse->json()['error']['code'] ?? 0);
        }

        $containerId = $containerResponse->json()['id'] ?? null;

        if (! $containerId) {
            throw new Exception('Failed to get container ID from Instagram');
        }

        // Step 2: Wait for video processing
        $this->waitForVideoProcessing($containerId, $accessToken);

        // Step 3: Publish Reels
        $publishResponse = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$igAccountId}/media_publish", [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ]);

        if ($publishResponse->failed()) {
            throw new Exception($publishResponse->json()['error']['message'] ?? 'Failed to publish Reels', $publishResponse->json()['error']['code'] ?? 0);
        }

        $mediaId = $publishResponse->json()['id'] ?? null;

        return [
            'status' => 1,
            'id' => $mediaId,
            'url' => $mediaId ? "https://www.instagram.com/reel/{$this->getShortcodeFromId($mediaId)}" : null,
        ];
    }

    /**
     * Wait for video processing to complete.
     */
    protected function waitForVideoProcessing(string $containerId, string $accessToken, int $maxAttempts = 30): void
    {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $statusResponse = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$containerId}", [
                'fields' => 'status_code',
                'access_token' => $accessToken,
            ]);

            if ($statusResponse->successful()) {
                $statusCode = $statusResponse->json()['status_code'] ?? null;

                if ($statusCode === 'FINISHED') {
                    return;
                }

                if ($statusCode === 'ERROR') {
                    throw new Exception('Video processing failed');
                }
            }

            sleep(2); // Wait 2 seconds before next check
            $attempt++;
        }

        throw new Exception('Video processing timeout');
    }

    /**
     * Get Instagram shortcode from media ID (simplified).
     */
    protected function getShortcodeFromId(string $mediaId): string
    {
        // Instagram media IDs can be converted to shortcodes
        // For simplicity, we'll just return the ID here
        // In production, you'd want to implement the proper conversion algorithm
        return $mediaId;
    }
}

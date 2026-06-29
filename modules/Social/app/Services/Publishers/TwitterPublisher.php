<?php

namespace Modules\Social\Services\Publishers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Social\Enums\PostType;
use Modules\Social\Models\Post;

class TwitterPublisher extends BasePublisher
{
    /**
     * Twitter API base URL.
     */
    protected string $apiBase = 'https://api.twitter.com/2';

    /**
     * Twitter Media Upload URL.
     */
    protected string $uploadBase = 'https://upload.twitter.com/1.1';

    /**
     * Publish post to Twitter.
     */
    public function publish(Post $post): array
    {
        $accessToken = decrypt($post->socialAccount->access_token);

        try {
            return match ($post->type) {
                PostType::TEXT => $this->publishText($accessToken, $post),
                PostType::IMAGE => $this->publishImage($accessToken, $post),
                PostType::VIDEO => $this->publishVideo($accessToken, $post),
                PostType::LINK => $this->publishLink($accessToken, $post),
                default => throw new Exception("Twitter does not support {$post->type->value} posts"),
            };
        } catch (Exception $e) {
            Log::error("Twitter publish error: {$e->getMessage()}", [
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

        // Twitter character limit
        if (mb_strlen($post->content) > 280) {
            $errors[] = 'Twitter posts cannot exceed 280 characters.';
        }

        // Media limit
        $mediaUrls = $this->getMediaUrls($post);
        if (count($mediaUrls) > 4) {
            $errors[] = 'Twitter supports maximum 4 images per tweet.';
        }

        // Video posts can only have one video
        if ($post->type === PostType::VIDEO && count($mediaUrls) > 1) {
            $errors[] = 'Twitter supports only one video per tweet.';
        }

        return $errors;
    }

    /**
     * Publish text-only tweet.
     */
    protected function publishText(string $accessToken, Post $post): array
    {
        $response = Http::withToken($accessToken)
            ->post("{$this->apiBase}/tweets", [
                'text' => $post->content,
            ]);

        if ($response->failed()) {
            throw new Exception($response->json()['detail'] ?? 'Failed to publish tweet', $response->status());
        }

        $data = $response->json()['data'] ?? [];
        $tweetId = $data['id'] ?? null;

        return [
            'status' => 1,
            'id' => $tweetId,
            'url' => $tweetId ? "https://twitter.com/i/web/status/{$tweetId}" : null,
        ];
    }

    /**
     * Publish image tweet.
     */
    protected function publishImage(string $accessToken, Post $post): array
    {
        $mediaUrls = $this->getMediaUrls($post);

        if (empty($mediaUrls)) {
            throw new Exception('No media found for image tweet');
        }

        // Upload media files first
        $mediaIds = [];
        foreach (array_slice($mediaUrls, 0, 4) as $mediaUrl) {
            $mediaId = $this->uploadMedia($accessToken, $mediaUrl);
            if ($mediaId) {
                $mediaIds[] = $mediaId;
            }
        }

        if (empty($mediaIds)) {
            throw new Exception('Failed to upload media to Twitter');
        }

        // Create tweet with media
        $response = Http::withToken($accessToken)
            ->post("{$this->apiBase}/tweets", [
                'text' => $post->content,
                'media' => [
                    'media_ids' => $mediaIds,
                ],
            ]);

        if ($response->failed()) {
            throw new Exception($response->json()['detail'] ?? 'Failed to publish tweet with media', $response->status());
        }

        $data = $response->json()['data'] ?? [];
        $tweetId = $data['id'] ?? null;

        return [
            'status' => 1,
            'id' => $tweetId,
            'url' => $tweetId ? "https://twitter.com/i/web/status/{$tweetId}" : null,
        ];
    }

    /**
     * Publish video tweet.
     */
    protected function publishVideo(string $accessToken, Post $post): array
    {
        $mediaUrls = $this->getMediaUrls($post);

        if (empty($mediaUrls)) {
            throw new Exception('No media found for video tweet');
        }

        // Upload video
        $mediaId = $this->uploadMedia($accessToken, $mediaUrls[0], 'video');

        if (! $mediaId) {
            throw new Exception('Failed to upload video to Twitter');
        }

        // Wait for video processing
        $this->waitForVideoProcessing($accessToken, $mediaId);

        // Create tweet with video
        $response = Http::withToken($accessToken)
            ->post("{$this->apiBase}/tweets", [
                'text' => $post->content,
                'media' => [
                    'media_ids' => [$mediaId],
                ],
            ]);

        if ($response->failed()) {
            throw new Exception($response->json()['detail'] ?? 'Failed to publish video tweet', $response->status());
        }

        $data = $response->json()['data'] ?? [];
        $tweetId = $data['id'] ?? null;

        return [
            'status' => 1,
            'id' => $tweetId,
            'url' => $tweetId ? "https://twitter.com/i/web/status/{$tweetId}" : null,
        ];
    }

    /**
     * Publish link tweet.
     */
    protected function publishLink(string $accessToken, Post $post): array
    {
        $link = $post->link;

        if (empty($link)) {
            throw new Exception('Link is required for link tweets');
        }

        // Twitter auto-expands links, just include in text
        $text = $post->content ? "{$post->content}\n\n{$link}" : $link;

        $response = Http::withToken($accessToken)
            ->post("{$this->apiBase}/tweets", [
                'text' => $text,
            ]);

        if ($response->failed()) {
            throw new Exception($response->json()['detail'] ?? 'Failed to publish link tweet', $response->status());
        }

        $data = $response->json()['data'] ?? [];
        $tweetId = $data['id'] ?? null;

        return [
            'status' => 1,
            'id' => $tweetId,
            'url' => $tweetId ? "https://twitter.com/i/web/status/{$tweetId}" : null,
        ];
    }

    /**
     * Upload media to Twitter.
     */
    protected function uploadMedia(string $accessToken, string $mediaUrl, string $mediaType = 'image'): ?string
    {
        try {
            $fullUrl = $this->getFullMediaUrl($mediaUrl);

            // Download media file
            $mediaContent = file_get_contents($fullUrl);

            if (! $mediaContent) {
                throw new Exception("Failed to download media from {$fullUrl}");
            }

            // Determine media category
            $mediaCategory = $mediaType === 'video' ? 'tweet_video' : 'tweet_image';

            // INIT upload
            $initResponse = Http::withToken($accessToken)
                ->asForm()
                ->post("{$this->uploadBase}/media/upload.json", [
                    'command' => 'INIT',
                    'total_bytes' => strlen($mediaContent),
                    'media_type' => $this->isVideo($mediaUrl) ? 'video/mp4' : 'image/jpeg',
                    'media_category' => $mediaCategory,
                ]);

            if ($initResponse->failed()) {
                throw new Exception('Failed to initialize media upload');
            }

            $mediaId = $initResponse->json()['media_id_string'] ?? null;

            if (! $mediaId) {
                throw new Exception('Failed to get media ID from Twitter');
            }

            // APPEND upload
            $appendResponse = Http::withToken($accessToken)
                ->asForm()
                ->attach('media', $mediaContent, 'media')
                ->post("{$this->uploadBase}/media/upload.json", [
                    'command' => 'APPEND',
                    'media_id' => $mediaId,
                    'segment_index' => 0,
                ]);

            if ($appendResponse->failed()) {
                throw new Exception('Failed to append media data');
            }

            // FINALIZE upload
            $finalizeResponse = Http::withToken($accessToken)
                ->asForm()
                ->post("{$this->uploadBase}/media/upload.json", [
                    'command' => 'FINALIZE',
                    'media_id' => $mediaId,
                ]);

            if ($finalizeResponse->failed()) {
                throw new Exception('Failed to finalize media upload');
            }

            return $mediaId;
        } catch (Exception $e) {
            Log::error("Twitter media upload error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Wait for video processing to complete.
     */
    protected function waitForVideoProcessing(string $accessToken, string $mediaId, int $maxAttempts = 30): void
    {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $statusResponse = Http::withToken($accessToken)
                ->get("{$this->uploadBase}/media/upload.json", [
                    'command' => 'STATUS',
                    'media_id' => $mediaId,
                ]);

            if ($statusResponse->successful()) {
                $processingInfo = $statusResponse->json()['processing_info'] ?? null;

                if (! $processingInfo) {
                    return; // No processing needed
                }

                $state = $processingInfo['state'] ?? null;

                if ($state === 'succeeded') {
                    return;
                }

                if ($state === 'failed') {
                    throw new Exception('Video processing failed: '.($processingInfo['error']['message'] ?? 'Unknown error'));
                }

                $checkAfterSecs = $processingInfo['check_after_secs'] ?? 5;
                sleep($checkAfterSecs);
            }

            $attempt++;
        }

        throw new Exception('Video processing timeout');
    }
}

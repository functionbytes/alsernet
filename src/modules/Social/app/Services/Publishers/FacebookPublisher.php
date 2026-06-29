<?php

namespace Modules\Social\Services\Publishers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Social\Enums\PostType;
use Modules\Social\Models\Post;

class FacebookPublisher extends BasePublisher
{
    /**
     * Facebook Graph API version.
     */
    protected string $graphVersion = 'v21.0';

    /**
     * Publish post to Facebook.
     */
    public function publish(Post $post): array
    {
        $accessToken = decrypt($post->socialAccount->access_token);
        $pageId = $post->socialAccount->network_id;

        try {
            return match ($post->type) {
                PostType::TEXT => $this->publishText($pageId, $accessToken, $post),
                PostType::IMAGE => $this->publishImage($pageId, $accessToken, $post),
                PostType::VIDEO => $this->publishVideo($pageId, $accessToken, $post),
                PostType::LINK => $this->publishLink($pageId, $accessToken, $post),
                PostType::CAROUSEL => $this->publishCarousel($pageId, $accessToken, $post),
                default => throw new Exception("Unsupported post type: {$post->type->value}"),
            };
        } catch (Exception $e) {
            Log::error("Facebook publish error: {$e->getMessage()}", [
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

        // Video posts need exactly one video
        if ($post->type === PostType::VIDEO) {
            if (empty($mediaUrls)) {
                $errors[] = 'Video posts require at least one video file.';
            } elseif (count($mediaUrls) > 1) {
                $errors[] = 'Facebook only supports one video per post.';
            } elseif (! $this->isVideo($mediaUrls[0])) {
                $errors[] = 'Media file must be a video for video posts.';
            }
        }

        // Carousel needs 2-10 images
        if ($post->type === PostType::CAROUSEL) {
            if (count($mediaUrls) < 2) {
                $errors[] = 'Carousel posts require at least 2 images.';
            } elseif (count($mediaUrls) > 10) {
                $errors[] = 'Facebook carousel posts support maximum 10 images.';
            }
        }

        return $errors;
    }

    /**
     * Publish text-only post.
     */
    protected function publishText(string $pageId, string $accessToken, Post $post): array
    {
        $response = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$pageId}/feed", [
            'message' => $post->content,
            'access_token' => $accessToken,
        ]);

        if ($response->failed()) {
            throw new Exception($response->json()['error']['message'] ?? 'Failed to publish text post', $response->json()['error']['code'] ?? 0);
        }

        $data = $response->json();

        return [
            'status' => 1,
            'id' => $data['id'] ?? null,
            'url' => isset($data['id']) ? "https://www.facebook.com/{$data['id']}" : null,
        ];
    }

    /**
     * Publish image post (single or multiple).
     */
    protected function publishImage(string $pageId, string $accessToken, Post $post): array
    {
        $mediaUrls = $this->getMediaUrls($post);

        if (empty($mediaUrls)) {
            throw new Exception('No media found for image post');
        }

        // Single image
        if (count($mediaUrls) === 1) {
            $response = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$pageId}/photos", [
                'message' => $post->content,
                'url' => $this->getFullMediaUrl($mediaUrls[0]),
                'access_token' => $accessToken,
            ]);

            if ($response->failed()) {
                throw new Exception($response->json()['error']['message'] ?? 'Failed to publish image', $response->json()['error']['code'] ?? 0);
            }

            $data = $response->json();

            return [
                'status' => 1,
                'id' => $data['id'] ?? null,
                'url' => isset($data['id']) ? "https://www.facebook.com/{$data['id']}" : null,
            ];
        }

        // Multiple images - upload each unpublished first
        $attachedMedia = [];
        foreach ($mediaUrls as $index => $mediaUrl) {
            $uploadResponse = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$pageId}/photos", [
                'url' => $this->getFullMediaUrl($mediaUrl),
                'published' => 'false',
                'access_token' => $accessToken,
            ]);

            if ($uploadResponse->successful() && isset($uploadResponse->json()['id'])) {
                $attachedMedia["attached_media[{$index}]"] = json_encode(['media_fbid' => $uploadResponse->json()['id']]);
            }
        }

        if (empty($attachedMedia)) {
            throw new Exception('Failed to upload images to Facebook');
        }

        // Publish feed post with attached media
        $response = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$pageId}/feed", array_merge([
            'message' => $post->content,
            'access_token' => $accessToken,
        ], $attachedMedia));

        if ($response->failed()) {
            throw new Exception($response->json()['error']['message'] ?? 'Failed to publish album', $response->json()['error']['code'] ?? 0);
        }

        $data = $response->json();

        return [
            'status' => 1,
            'id' => $data['id'] ?? null,
            'url' => isset($data['id']) ? "https://www.facebook.com/{$data['id']}" : null,
        ];
    }

    /**
     * Publish video post.
     */
    protected function publishVideo(string $pageId, string $accessToken, Post $post): array
    {
        $mediaUrls = $this->getMediaUrls($post);

        if (empty($mediaUrls)) {
            throw new Exception('No media found for video post');
        }

        $response = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$pageId}/videos", [
            'description' => $post->content,
            'file_url' => $this->getFullMediaUrl($mediaUrls[0]),
            'access_token' => $accessToken,
        ]);

        if ($response->failed()) {
            throw new Exception($response->json()['error']['message'] ?? 'Failed to publish video', $response->json()['error']['code'] ?? 0);
        }

        $data = $response->json();

        return [
            'status' => 1,
            'id' => $data['id'] ?? null,
            'url' => isset($data['id']) ? "https://www.facebook.com/{$data['id']}" : null,
        ];
    }

    /**
     * Publish link post.
     */
    protected function publishLink(string $pageId, string $accessToken, Post $post): array
    {
        $link = $post->link;

        if (empty($link)) {
            throw new Exception('Link is required for link posts');
        }

        $response = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$pageId}/feed", [
            'message' => $post->content,
            'link' => $link,
            'access_token' => $accessToken,
        ]);

        if ($response->failed()) {
            throw new Exception($response->json()['error']['message'] ?? 'Failed to publish link', $response->json()['error']['code'] ?? 0);
        }

        $data = $response->json();

        return [
            'status' => 1,
            'id' => $data['id'] ?? null,
            'url' => isset($data['id']) ? "https://www.facebook.com/{$data['id']}" : null,
        ];
    }

    /**
     * Publish carousel post (multiple images).
     */
    protected function publishCarousel(string $pageId, string $accessToken, Post $post): array
    {
        // Carousel is same as multiple images for Facebook
        return $this->publishImage($pageId, $accessToken, $post);
    }
}

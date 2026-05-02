<?php

namespace Modules\Social\Services\Publishers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Social\Enums\PostType;
use Modules\Social\Models\Post;

class LinkedInPublisher extends BasePublisher
{
    /**
     * LinkedIn API base URL.
     */
    protected string $apiBase = 'https://api.linkedin.com/v2';

    /**
     * Publish post to LinkedIn.
     */
    public function publish(Post $post): array
    {
        $accessToken = decrypt($post->socialAccount->access_token);
        $author = "urn:li:person:{$post->socialAccount->network_id}";

        try {
            return match ($post->type) {
                PostType::TEXT => $this->publishText($accessToken, $author, $post),
                PostType::IMAGE => $this->publishImage($accessToken, $author, $post),
                PostType::VIDEO => $this->publishVideo($accessToken, $author, $post),
                PostType::LINK => $this->publishLink($accessToken, $author, $post),
                default => throw new Exception("LinkedIn does not support {$post->type->value} posts"),
            };
        } catch (Exception $e) {
            Log::error("LinkedIn publish error: {$e->getMessage()}", [
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

        // LinkedIn character limit
        if (mb_strlen($post->content) > 3000) {
            $errors[] = 'LinkedIn posts cannot exceed 3000 characters.';
        }

        // Media limit
        $mediaUrls = $this->getMediaUrls($post);
        if (count($mediaUrls) > 9) {
            $errors[] = 'LinkedIn supports maximum 9 images per post.';
        }

        // Video posts can only have one video
        if ($post->type === PostType::VIDEO && count($mediaUrls) > 1) {
            $errors[] = 'LinkedIn supports only one video per post.';
        }

        return $errors;
    }

    /**
     * Publish text-only post.
     */
    protected function publishText(string $accessToken, string $author, Post $post): array
    {
        $payload = [
            'author' => $author,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $post->content,
                    ],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'X-Restli-Protocol-Version' => '2.0.0',
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->apiBase}/ugcPosts", $payload);

        if ($response->failed()) {
            throw new Exception($response->json()['message'] ?? 'Failed to publish LinkedIn post', $response->status());
        }

        $postId = $response->json()['id'] ?? null;

        return [
            'status' => 1,
            'id' => $postId,
            'url' => null, // LinkedIn doesn't provide direct URL in response
        ];
    }

    /**
     * Publish image post.
     */
    protected function publishImage(string $accessToken, string $author, Post $post): array
    {
        $mediaUrls = $this->getMediaUrls($post);

        if (empty($mediaUrls)) {
            throw new Exception('No media found for image post');
        }

        // Upload images and get asset URNs
        $mediaAssets = [];
        foreach (array_slice($mediaUrls, 0, 9) as $mediaUrl) {
            $assetUrn = $this->uploadImage($accessToken, $author, $mediaUrl);
            if ($assetUrn) {
                $mediaAssets[] = [
                    'status' => 'READY',
                    'media' => $assetUrn,
                ];
            }
        }

        if (empty($mediaAssets)) {
            throw new Exception('Failed to upload images to LinkedIn');
        }

        // Create post with images
        $payload = [
            'author' => $author,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $post->content,
                    ],
                    'shareMediaCategory' => 'IMAGE',
                    'media' => $mediaAssets,
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'X-Restli-Protocol-Version' => '2.0.0',
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->apiBase}/ugcPosts", $payload);

        if ($response->failed()) {
            throw new Exception($response->json()['message'] ?? 'Failed to publish LinkedIn image post', $response->status());
        }

        $postId = $response->json()['id'] ?? null;

        return [
            'status' => 1,
            'id' => $postId,
            'url' => null,
        ];
    }

    /**
     * Publish video post.
     */
    protected function publishVideo(string $accessToken, string $author, Post $post): array
    {
        $mediaUrls = $this->getMediaUrls($post);

        if (empty($mediaUrls)) {
            throw new Exception('No media found for video post');
        }

        // Upload video
        $assetUrn = $this->uploadVideo($accessToken, $author, $mediaUrls[0]);

        if (! $assetUrn) {
            throw new Exception('Failed to upload video to LinkedIn');
        }

        // Create post with video
        $payload = [
            'author' => $author,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $post->content,
                    ],
                    'shareMediaCategory' => 'VIDEO',
                    'media' => [
                        [
                            'status' => 'READY',
                            'media' => $assetUrn,
                        ],
                    ],
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'X-Restli-Protocol-Version' => '2.0.0',
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->apiBase}/ugcPosts", $payload);

        if ($response->failed()) {
            throw new Exception($response->json()['message'] ?? 'Failed to publish LinkedIn video post', $response->status());
        }

        $postId = $response->json()['id'] ?? null;

        return [
            'status' => 1,
            'id' => $postId,
            'url' => null,
        ];
    }

    /**
     * Publish link post.
     */
    protected function publishLink(string $accessToken, string $author, Post $post): array
    {
        $link = $post->link;

        if (empty($link)) {
            throw new Exception('Link is required for link posts');
        }

        $payload = [
            'author' => $author,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $post->content,
                    ],
                    'shareMediaCategory' => 'ARTICLE',
                    'media' => [
                        [
                            'status' => 'READY',
                            'originalUrl' => $link,
                        ],
                    ],
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'X-Restli-Protocol-Version' => '2.0.0',
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->apiBase}/ugcPosts", $payload);

        if ($response->failed()) {
            throw new Exception($response->json()['message'] ?? 'Failed to publish LinkedIn link post', $response->status());
        }

        $postId = $response->json()['id'] ?? null;

        return [
            'status' => 1,
            'id' => $postId,
            'url' => null,
        ];
    }

    /**
     * Upload image to LinkedIn.
     */
    protected function uploadImage(string $accessToken, string $author, string $mediaUrl): ?string
    {
        try {
            // Step 1: Register upload
            $registerResponse = Http::withToken($accessToken)
                ->withHeaders([
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->apiBase}/assets?action=registerUpload", [
                    'registerUploadRequest' => [
                        'recipes' => ['urn:li:digitalmediaRecipe:feedshare-image'],
                        'owner' => $author,
                        'serviceRelationships' => [
                            [
                                'relationshipType' => 'OWNER',
                                'identifier' => 'urn:li:userGeneratedContent',
                            ],
                        ],
                    ],
                ]);

            if ($registerResponse->failed()) {
                throw new Exception('Failed to register image upload');
            }

            $uploadUrl = $registerResponse->json()['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'] ?? null;
            $asset = $registerResponse->json()['value']['asset'] ?? null;

            if (! $uploadUrl || ! $asset) {
                throw new Exception('Failed to get upload URL or asset URN');
            }

            // Step 2: Upload image binary
            $fullUrl = $this->getFullMediaUrl($mediaUrl);
            $imageContent = file_get_contents($fullUrl);

            if (! $imageContent) {
                throw new Exception("Failed to download image from {$fullUrl}");
            }

            $uploadResponse = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($imageContent, 'application/octet-stream')
                ->put($uploadUrl);

            if ($uploadResponse->failed()) {
                throw new Exception('Failed to upload image binary');
            }

            return $asset;
        } catch (Exception $e) {
            Log::error("LinkedIn image upload error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Upload video to LinkedIn.
     */
    protected function uploadVideo(string $accessToken, string $author, string $mediaUrl): ?string
    {
        try {
            // Step 1: Register upload
            $registerResponse = Http::withToken($accessToken)
                ->withHeaders([
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->apiBase}/assets?action=registerUpload", [
                    'registerUploadRequest' => [
                        'recipes' => ['urn:li:digitalmediaRecipe:feedshare-video'],
                        'owner' => $author,
                        'serviceRelationships' => [
                            [
                                'relationshipType' => 'OWNER',
                                'identifier' => 'urn:li:userGeneratedContent',
                            ],
                        ],
                    ],
                ]);

            if ($registerResponse->failed()) {
                throw new Exception('Failed to register video upload');
            }

            $uploadUrl = $registerResponse->json()['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'] ?? null;
            $asset = $registerResponse->json()['value']['asset'] ?? null;

            if (! $uploadUrl || ! $asset) {
                throw new Exception('Failed to get upload URL or asset URN');
            }

            // Step 2: Upload video binary
            $fullUrl = $this->getFullMediaUrl($mediaUrl);
            $videoContent = file_get_contents($fullUrl);

            if (! $videoContent) {
                throw new Exception("Failed to download video from {$fullUrl}");
            }

            $uploadResponse = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($videoContent, 'application/octet-stream')
                ->put($uploadUrl);

            if ($uploadResponse->failed()) {
                throw new Exception('Failed to upload video binary');
            }

            return $asset;
        } catch (Exception $e) {
            Log::error("LinkedIn video upload error: {$e->getMessage()}");

            return null;
        }
    }
}

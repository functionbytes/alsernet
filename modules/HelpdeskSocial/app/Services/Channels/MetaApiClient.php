<?php

namespace Modules\HelpdeskSocial\Services\Channels;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;

class MetaApiClient implements SocialApiClientInterface
{
    private const BASE_URL = 'https://graph.facebook.com';

    private string $apiVersion;

    public function __construct()
    {
        $this->apiVersion = config('helpdesksocial.integrations.meta.api_version', 'v25.0');
    }

    public function replyToComment(string $commentId, string $message, string $accessToken, ?string $platform = null): ?string
    {
        $endpoint = $platform === 'instagram'
            ? "/{$commentId}/replies"
            : "/{$commentId}/comments";

        $response = $this->request($accessToken)
            ->post($endpoint, ['message' => $message]);

        if ($response->failed()) {
            Log::warning('MetaApiClient: replyToComment failed', [
                'comment_id' => $commentId,
                'platform' => $platform,
                'error' => $response->json(),
            ]);

            return null;
        }

        return $response->json('id');
    }

    public function hideComment(string $commentId, bool $hidden, string $accessToken): bool
    {
        $response = $this->request($accessToken)
            ->post("/{$commentId}", ['is_hidden' => $hidden]);

        return $response->successful();
    }

    public function deleteComment(string $commentId, string $accessToken): bool
    {
        $response = $this->request($accessToken)
            ->delete("/{$commentId}");

        return $response->successful();
    }

    public function getComments(string $postId, string $accessToken, int $limit = 100): array
    {
        $cacheKey = "meta_comments:{$postId}:{$limit}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($postId, $accessToken, $limit) {
            $response = $this->request($accessToken)
                ->get("/{$postId}/comments", [
                    'fields' => 'id,message,created_time,from{id,name},parent{id},attachment,comment_count',
                    'limit' => $limit,
                    'order' => 'reverse_chronological',
                ]);

            if ($response->failed()) {
                Log::warning('MetaApiClient: getComments failed', [
                    'post_id' => $postId,
                    'error' => $response->json(),
                ]);

                return [];
            }

            return $response->json('data', []);
        });
    }

    /**
     * Get comments on an Instagram media.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInstagramMediaComments(string $mediaId, string $accessToken, int $limit = 100): array
    {
        $cacheKey = "meta_ig_comments:{$mediaId}:{$limit}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($mediaId, $accessToken, $limit) {
            $response = $this->request($accessToken)
                ->get("/{$mediaId}/comments", [
                    'fields' => 'id,text,timestamp,user{id,username},replies{id,text,timestamp}',
                    'limit' => $limit,
                ]);

            if ($response->failed()) {
                Log::warning('MetaApiClient: getInstagramMediaComments failed', [
                    'media_id' => $mediaId,
                    'error' => $response->json(),
                ]);

                return [];
            }

            return $response->json('data', []);
        });
    }

    public function sendMessage(string $recipientId, array $message, string $accessToken): ?string
    {
        $response = $this->request($accessToken)
            ->post('/me/messages', [
                'recipient' => ['id' => $recipientId],
                'message' => $message,
                'messaging_type' => 'RESPONSE',
            ]);

        if ($response->failed()) {
            Log::warning('MetaApiClient: sendMessage failed', [
                'recipient_id' => $recipientId,
                'error' => $response->json(),
            ]);

            return null;
        }

        return $response->json('message_id');
    }

    public function getUserProfile(string $userId, string $accessToken): array
    {
        $cacheKey = "meta_profile:{$userId}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($userId, $accessToken) {
            $response = $this->request($accessToken)
                ->get("/{$userId}", ['fields' => 'id,name,profile_pic']);

            if ($response->failed()) {
                return [];
            }

            return $response->json();
        });
    }

    public function exchangeToken(string $shortLivedToken, string $appId, string $appSecret): ?string
    {
        $response = Http::get("{$this->baseUrl()}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if ($response->failed()) {
            Log::warning('MetaApiClient: token exchange failed', [
                'error' => $response->json(),
            ]);

            return null;
        }

        return $response->json('access_token');
    }

    /**
     * Get page access token from user token.
     */
    public function getPageAccessToken(string $pageId, string $userAccessToken): ?string
    {
        $response = $this->request($userAccessToken)
            ->get("/{$pageId}", ['fields' => 'access_token']);

        if ($response->failed()) {
            return null;
        }

        return $response->json('access_token');
    }

    private function request(string $accessToken): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($accessToken)
            ->timeout(30)
            ->connectTimeout(10);
    }

    private function baseUrl(): string
    {
        return self::BASE_URL.'/'.$this->apiVersion;
    }
}

<?php

namespace Modules\HelpdeskSocial\Services\Channels;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Exceptions\MetaTokenInvalidException;

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

        $response = $this->requestWithoutRetry($accessToken)
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
        $response = $this->requestWithoutRetry($accessToken)
            ->post("/{$commentId}", ['is_hidden' => $hidden]);

        return $response->successful();
    }

    public function deleteComment(string $commentId, string $accessToken): bool
    {
        $response = $this->requestWithoutRetry($accessToken)
            ->delete("/{$commentId}");

        return $response->successful();
    }

    public function getComments(string $postId, string $accessToken, int $limit = 100): array
    {
        return $this->fetchCommentsCached(
            "meta_comments:{$postId}:{$limit}",
            "/{$postId}/comments",
            [
                'fields' => 'id,message,created_time,from{id,name},parent{id},attachment,comment_count',
                'limit' => $limit,
                'order' => 'reverse_chronological',
            ],
            $accessToken,
            'getComments',
        );
    }

    /**
     * Get comments on an Instagram media.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInstagramMediaComments(string $mediaId, string $accessToken, int $limit = 100): array
    {
        return $this->fetchCommentsCached(
            "meta_ig_comments:{$mediaId}:{$limit}",
            "/{$mediaId}/comments",
            [
                'fields' => 'id,text,timestamp,user{id,username},replies{id,text,timestamp}',
                'limit' => $limit,
            ],
            $accessToken,
            'getInstagramMediaComments',
        );
    }

    /**
     * Solo cachea respuestas correctas: cachear [] ante un fallo ocultaría
     * 5 minutos los comentarios reales y haría pasar el fallo por éxito.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     *
     * @throws MetaTokenInvalidException
     */
    private function fetchCommentsCached(string $cacheKey, string $endpoint, array $query, string $accessToken, string $operation): array
    {
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $response = $this->request($accessToken)->get($endpoint, $query);

        if ($response->failed()) {
            $this->throwIfTokenInvalid($response);

            Log::warning("MetaApiClient: {$operation} failed", [
                'endpoint' => $endpoint,
                'error' => $response->json(),
            ]);

            return [];
        }

        $data = $response->json('data', []);
        Cache::put($cacheKey, $data, now()->addMinutes(5));

        return $data;
    }

    private function throwIfTokenInvalid(Response $response): void
    {
        if ($response->status() !== 401 && (int) $response->json('error.code') !== 190) {
            return;
        }

        throw new MetaTokenInvalidException(
            (string) ($response->json('error.message') ?? 'Meta access token is invalid or expired.')
        );
    }

    public function sendMessage(string $recipientId, array $message, string $accessToken): ?string
    {
        $response = $this->requestWithoutRetry($accessToken)
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
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $response = $this->request($accessToken)
            ->get("/{$userId}", ['fields' => 'id,name,profile_pic']);

        if ($response->failed()) {
            return [];
        }

        $profile = $response->json();
        Cache::put($cacheKey, $profile, now()->addHours(24));

        return $profile;
    }

    public function exchangeToken(string $shortLivedToken, string $appId, string $appSecret): ?string
    {
        // timeout/connectTimeout/retry como el resto de llamadas de esta clase
        // (request()); el token va como query param, no como Bearer, así que no
        // se puede reusar request() directamente.
        $response = Http::timeout(30)
            ->connectTimeout(10)
            ->retry(3, 500, throw: false)
            ->get("{$this->baseUrl()}/oauth/access_token", [
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

    /**
     * Solo para GET/lectura: reintenta automáticamente. Las llamadas que
     * mutan/envían algo (replyToComment, hideComment, deleteComment,
     * sendMessage) usan requestWithoutRetry() — reintentar tras enviar el
     * cuerpo arriesga duplicar la acción (p.ej. publicar la misma respuesta
     * dos veces) si la petición original sí llegó pero la respuesta se perdió.
     */
    private function request(string $accessToken): PendingRequest
    {
        return $this->requestWithoutRetry($accessToken)->retry(3, 500, throw: false);
    }

    private function requestWithoutRetry(string $accessToken): PendingRequest
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

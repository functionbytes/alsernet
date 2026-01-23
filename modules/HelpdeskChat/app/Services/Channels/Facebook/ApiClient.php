<?php

namespace Modules\HelpdeskChat\Services\Channels\Facebook;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiClient
{
    protected string $graphApiUrl = 'https://graph.facebook.com/v18.0';

    protected ?string $appId;

    protected ?string $appSecret;

    protected ?string $verifyToken;

    public function __construct()
    {
        $this->appId = config('channels.facebook.app_id');
        $this->appSecret = config('channels.facebook.app_secret');
        $this->verifyToken = config('channels.facebook.verify_token');
    }

    /**
     * Get OAuth URL for Facebook login.
     */
    public function getOAuthUrl(): string
    {
        $callbackUrl = url('/admin/channels/facebook-pages/callback');
        $scope = 'pages_messaging,pages_manage_metadata,pages_read_engagement';

        return 'https://www.facebook.com/v18.0/dialog/oauth?'.http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $callbackUrl,
            'scope' => $scope,
            'response_type' => 'code',
        ]);
    }

    /**
     * Exchange authorization code for access token.
     */
    public function getAccessTokenFromCode(string $code): string
    {
        $callbackUrl = url('/admin/channels/facebook-pages/callback');

        $response = Http::get($this->graphApiUrl.'/oauth/access_token', [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri' => $callbackUrl,
            'code' => $code,
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to get access token: '.$response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Get user's Facebook pages.
     */
    public function getUserPages(string $userAccessToken): array
    {
        $response = Http::get($this->graphApiUrl.'/me/accounts', [
            'access_token' => $userAccessToken,
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to get user pages: '.$response->body());
        }

        return $response->json('data', []);
    }

    /**
     * Get long-lived page access token.
     */
    public function getLongLivedPageToken(string $shortLivedToken): string
    {
        $response = Http::get($this->graphApiUrl.'/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to get long-lived token: '.$response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Subscribe page to app webhooks.
     */
    public function subscribePageToApp(string $pageAccessToken): bool
    {
        $response = Http::post($this->graphApiUrl.'/me/subscribed_apps', [
            'access_token' => $pageAccessToken,
            'subscribed_fields' => 'messages,messaging_postbacks,message_deliveries,message_reads',
        ]);

        if ($response->failed()) {
            Log::error('Failed to subscribe page to app', [
                'response' => $response->body(),
            ]);

            return false;
        }

        return $response->json('success', false);
    }

    /**
     * Send a text message to a user.
     */
    public function sendMessage(string $pageAccessToken, string $recipientId, string $message): array
    {
        $response = Http::post($this->graphApiUrl.'/me/messages', [
            'access_token' => $pageAccessToken,
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $message],
            'messaging_type' => 'RESPONSE',
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to send message: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Get user profile information.
     */
    public function getUserProfile(string $pageAccessToken, string $userId): array
    {
        $response = Http::get($this->graphApiUrl."/{$userId}", [
            'access_token' => $pageAccessToken,
            'fields' => 'first_name,last_name,profile_pic',
        ]);

        if ($response->failed()) {
            Log::error('Failed to get user profile', [
                'user_id' => $userId,
                'response' => $response->body(),
            ]);

            return [];
        }

        return $response->json();
    }

    /**
     * Verify webhook signature.
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $this->appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get verify token for webhook verification.
     */
    public function getVerifyToken(): string
    {
        return $this->verifyToken;
    }
}

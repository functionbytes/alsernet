<?php

namespace Modules\Chat\Services\Channels\Instagram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiClient
{
    protected string $graphApiUrl = 'https://graph.facebook.com/v24.0';

    protected string $authUrl = 'https://www.facebook.com/v24.0/dialog/oauth';

    /**
     * Generate OAuth URL for Instagram Business login via Facebook.
     */
    public function getOAuthUrl(): string
    {
        $params = [
            'client_id' => config('channels.facebook.app_id'),
            'redirect_uri' => route('settings.chat.channels.instagrams.callback'),
            'scope' => 'instagram_basic,instagram_manage_messages,pages_show_list,pages_messaging',
            'response_type' => 'code',
            'state' => $this->generateState(),
        ];

        return $this->authUrl.'?'.http_build_query($params);
    }

    /**
     * Exchange authorization code for access token.
     */
    public function getAccessTokenFromCode(string $code): string
    {
        $payload = [
            'client_id' => config('channels.facebook.app_id'),
            'client_secret' => config('channels.facebook.app_secret'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('settings.chat.channels.instagrams.callback'),
            'code' => $code,
        ];

        Log::info('Instagram OAuth Token Exchange Request', [
            'url' => $this->graphApiUrl.'/oauth/access_token',
            'client_id' => $payload['client_id'],
            'redirect_uri' => $payload['redirect_uri'],
            'code' => substr($code, 0, 20).'...',
        ]);

        $response = Http::post($this->graphApiUrl.'/oauth/access_token', $payload);

        Log::info('Instagram OAuth Token Exchange Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to exchange code for access token: '.$response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Get Instagram Business accounts for the authenticated user.
     * Instagram Business accounts are connected to Facebook Pages.
     */
    public function getUserInstagramAccounts(string $accessToken): array
    {
        // First, get all Facebook Pages the user manages
        $response = Http::get($this->graphApiUrl.'/me/accounts', [
            'access_token' => $accessToken,
            'fields' => 'id,name,access_token,instagram_business_account',
        ]);

        if ($response->failed()) {
            Log::error('Failed to get user Facebook Pages', [
                'response' => $response->body(),
            ]);

            return [];
        }

        $pages = $response->json('data', []);
        $accounts = [];

        // For each Facebook Page, check if it has an Instagram Business Account
        foreach ($pages as $page) {
            if (isset($page['instagram_business_account']['id'])) {
                $accountId = $page['instagram_business_account']['id'];

                // Get detailed Instagram account info
                $accountInfo = $this->getAccountInfo($accessToken, $accountId);

                if (! empty($accountInfo)) {
                    $accounts[] = [
                        'id' => $accountId,
                        'facebook_page_id' => $page['id'],
                        'facebook_page_name' => $page['name'],
                        'page_access_token' => $page['access_token'] ?? null,
                        'username' => $accountInfo['username'] ?? 'Unknown',
                        'profile_picture_url' => $accountInfo['profile_picture_url'] ?? null,
                        'followers_count' => $accountInfo['followers_count'] ?? 0,
                    ];
                }
            }
        }

        Log::info('Retrieved Instagram Business Accounts', [
            'count' => count($accounts),
            'accounts' => array_map(fn ($a) => $a['username'] ?? 'unknown', $accounts),
        ]);

        return $accounts;
    }

    /**
     * Generate a secure state parameter for OAuth.
     */
    protected function generateState(): string
    {
        $payload = json_encode([
            'sub' => auth()->id(),
            'iat' => now()->timestamp,
        ]);

        return base64_encode(hash_hmac('sha256', $payload, config('app.key'), true));
    }

    /**
     * Send a text message to a user via Instagram.
     */
    public function sendMessage(string $accessToken, string $instagramAccountId, string $recipientId, string $message): array
    {
        $response = Http::post($this->graphApiUrl."/{$instagramAccountId}/messages", [
            'access_token' => $accessToken,
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $message],
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to send Instagram message: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Get Instagram account information.
     */
    public function getAccountInfo(string $accessToken, string $instagramId): array
    {
        $response = Http::get($this->graphApiUrl."/{$instagramId}", [
            'access_token' => $accessToken,
            'fields' => 'username,profile_picture_url,followers_count',
        ]);

        if ($response->failed()) {
            Log::error('Failed to get Instagram account info', [
                'instagram_id' => $instagramId,
                'response' => $response->body(),
            ]);

            return [];
        }

        return $response->json();
    }

    /**
     * Get user profile information.
     */
    public function getUserProfile(string $accessToken, string $userId): array
    {
        $response = Http::get($this->graphApiUrl."/{$userId}", [
            'access_token' => $accessToken,
            'fields' => 'name,username,profile_pic',
        ]);

        if ($response->failed()) {
            Log::error('Failed to get Instagram user profile', [
                'user_id' => $userId,
                'response' => $response->body(),
            ]);

            return [];
        }

        return $response->json();
    }

    /**
     * Subscribe Instagram account to app webhooks.
     */
    public function subscribeToWebhooks(string $accessToken, string $instagramId): bool
    {
        $response = Http::post($this->graphApiUrl."/{$instagramId}/subscribed_apps", [
            'access_token' => $accessToken,
            'subscribed_fields' => 'messages,messaging_postbacks,messaging_seen,message_reactions',
        ]);

        if ($response->failed()) {
            Log::error('Failed to subscribe Instagram to webhooks', [
                'instagram_id' => $instagramId,
                'response' => $response->body(),
            ]);

            return false;
        }

        return $response->json('success', false);
    }

    /**
     * Refresh long-lived access token.
     */
    public function refreshAccessToken(string $accessToken): array
    {
        $response = Http::get($this->graphApiUrl.'/oauth/access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $accessToken,
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to refresh Instagram access token: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Get Instagram Business Accounts connected to a Facebook Page.
     */
    public function getConnectedInstagramAccounts(string $pageAccessToken, string $pageId): array
    {
        $response = Http::get($this->graphApiUrl."/{$pageId}", [
            'access_token' => $pageAccessToken,
            'fields' => 'instagram_business_account',
        ]);

        if ($response->failed()) {
            Log::error('Failed to get connected Instagram accounts', [
                'page_id' => $pageId,
                'response' => $response->body(),
            ]);

            return [];
        }

        return $response->json('instagram_business_account', []);
    }

    /**
     * Send an attachment (image, video, file) via Instagram.
     */
    public function sendAttachment(string $accessToken, string $instagramAccountId, string $recipientId, string $type, string $url): array
    {
        $response = Http::post($this->graphApiUrl."/{$instagramAccountId}/messages", [
            'access_token' => $accessToken,
            'recipient' => ['id' => $recipientId],
            'message' => [
                'attachment' => [
                    'type' => $type,
                    'payload' => [
                        'url' => $url,
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to send Instagram attachment: '.$response->body());
        }

        return $response->json();
    }
}

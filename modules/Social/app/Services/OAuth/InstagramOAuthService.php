<?php

namespace Modules\Social\Services\OAuth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class InstagramOAuthService extends BaseOAuthService
{
    /**
     * Redirect to Facebook OAuth (Instagram uses Facebook Login)
     */
    public function redirectToProvider(): RedirectResponse
    {
        return Socialite::driver('facebook')
            ->scopes([
                'instagram_basic',
                'instagram_content_publish',
                'instagram_manage_comments',
                'instagram_manage_insights',
                'pages_show_list',
                'pages_read_engagement',
            ])
            ->redirect();
    }

    /**
     * Handle Instagram OAuth callback
     */
    public function handleCallback(Request $request): array
    {
        $user = Socialite::driver('facebook')->user();

        // Get user's Facebook pages
        $response = Http::get('https://graph.facebook.com/v18.0/me/accounts', [
            'access_token' => $user->token,
        ]);

        $pages = $response->json()['data'] ?? [];

        if (empty($pages)) {
            throw new \Exception('No se encontraron páginas de Facebook. Necesitas una página de Facebook para conectar Instagram Business');
        }

        // Get Instagram Business Account connected to the first page
        $instagramAccounts = [];
        foreach ($pages as $page) {
            $igResponse = Http::get("https://graph.facebook.com/v18.0/{$page['id']}", [
                'fields' => 'instagram_business_account',
                'access_token' => $page['access_token'],
            ]);

            $igData = $igResponse->json();

            if (isset($igData['instagram_business_account']['id'])) {
                $instagramAccounts[] = [
                    'page' => $page,
                    'instagram_id' => $igData['instagram_business_account']['id'],
                ];
            }
        }

        if (empty($instagramAccounts)) {
            throw new \Exception('No se encontraron cuentas de Instagram Business conectadas a tus páginas de Facebook');
        }

        // Use first Instagram account
        $account = $instagramAccounts[0];
        $pageAccessToken = $account['page']['access_token'];
        $instagramId = $account['instagram_id'];

        // Get Instagram account details
        $profileResponse = Http::get("https://graph.facebook.com/v18.0/{$instagramId}", [
            'fields' => 'id,username,name,profile_picture_url,followers_count,follows_count,media_count',
            'access_token' => $pageAccessToken,
        ]);

        $profile = $profileResponse->json();

        // Get long-lived page access token
        $longLivedToken = $this->getLongLivedToken($pageAccessToken);

        return [
            'network_id' => $instagramId,
            'username' => $profile['username'] ?? $profile['name'],
            'name' => $profile['name'] ?? $profile['username'],
            'access_token' => $longLivedToken,
            'refresh_token' => null,
            'expires_at' => now()->addDays(60),
            'profile_data' => [
                'profile_picture' => $profile['profile_picture_url'] ?? null,
                'followers_count' => $profile['followers_count'] ?? 0,
                'follows_count' => $profile['follows_count'] ?? 0,
                'media_count' => $profile['media_count'] ?? 0,
                'facebook_page_id' => $account['page']['id'],
                'facebook_page_name' => $account['page']['name'],
            ],
        ];
    }

    /**
     * Exchange short-lived token for long-lived token
     */
    protected function getLongLivedToken(string $shortToken): string
    {
        $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.facebook.client_id'),
            'client_secret' => config('services.facebook.client_secret'),
            'fb_exchange_token' => $shortToken,
        ]);

        $data = $response->json();

        return $data['access_token'] ?? $shortToken;
    }

    /**
     * Refresh access token
     */
    public function refreshToken(string $refreshToken): array
    {
        throw new \Exception('Instagram no requiere refresh token. Usa long-lived tokens de Facebook.');
    }

    /**
     * Get Instagram profile
     */
    public function getUserProfile(string $accessToken): array
    {
        // Note: Need Instagram Business Account ID
        throw new \Exception('Use getUserProfile with Instagram ID parameter');
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhook(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, config('services.facebook.webhook_secret'));

        return hash_equals($expectedSignature, $signature);
    }
}

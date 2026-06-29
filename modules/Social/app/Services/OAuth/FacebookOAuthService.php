<?php

namespace Modules\Social\Services\OAuth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class FacebookOAuthService extends BaseOAuthService
{
    /**
     * Redirect to Facebook OAuth
     */
    public function redirectToProvider(): RedirectResponse
    {
        return Socialite::driver('facebook')
            ->scopes([
                'pages_show_list',
                'pages_read_engagement',
                'pages_manage_posts',
                'pages_manage_metadata',
                'pages_read_user_content',
                'pages_messaging',
                'instagram_basic',
                'instagram_content_publish',
            ])
            ->redirect();
    }

    /**
     * Handle Facebook OAuth callback
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
            throw new \Exception('No se encontraron páginas de Facebook asociadas a esta cuenta');
        }

        // Use first page or let user choose
        $page = $pages[0];

        // Get long-lived page access token
        $longLivedToken = $this->getLongLivedToken($page['access_token']);

        $avatar = $page['picture']['data']['url'] ?? null;
        if ($avatar) {
            try {
                // Download and store avatar
                $avatarData = file_get_contents($avatar);
                $filename = 'avatars/'.$page['id'].'_'.time().'.jpg';
                \Storage::put('public/'.$filename, $avatarData);
                $avatar = $filename;
            } catch (\Exception $e) {
                $avatar = null;
            }
        }

        return [
            'network_id' => $page['id'],
            'username' => $page['name'],
            'name' => $page['name'],
            'avatar' => $avatar,
            'category' => $page['category'] ?? null,
            'access_token' => $longLivedToken,
            'refresh_token' => null, // Facebook doesn't use refresh tokens
            'expires_at' => now()->addDays(60), // Long-lived tokens last ~60 days
            'profile_data' => [
                'fan_count' => $page['fan_count'] ?? 0,
                'category_list' => $page['category_list'] ?? [],
                'tasks' => $page['tasks'] ?? [],
                'is_verified' => $page['is_verified'] ?? false,
                'link' => $page['link'] ?? null,
                'all_pages' => $pages, // Store all pages for later selection
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
     * Refresh access token (Facebook uses long-lived tokens, no refresh needed)
     */
    public function refreshToken(string $refreshToken): array
    {
        throw new \Exception('Facebook no requiere refresh token. Usa long-lived tokens.');
    }

    /**
     * Get Facebook page profile
     */
    public function getUserProfile(string $accessToken): array
    {
        $response = Http::get('https://graph.facebook.com/v18.0/me', [
            'access_token' => $accessToken,
            'fields' => 'id,name,category,username,followers_count,fan_count',
        ]);

        return $response->json();
    }

    /**
     * Validate Facebook webhook signature
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

<?php

namespace Modules\Social\Services\OAuth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class TwitterOAuthService extends BaseOAuthService
{
    /**
     * Redirect to Twitter OAuth
     */
    public function redirectToProvider(): RedirectResponse
    {
        return Socialite::driver('twitter')
            ->redirect();
    }

    /**
     * Handle Twitter OAuth callback
     */
    public function handleCallback(Request $request): array
    {
        $user = Socialite::driver('twitter')->user();

        // Get user profile details from Twitter API v2
        $profile = $this->getUserProfile($user->token);

        return [
            'network_id' => $user->id,
            'username' => $user->nickname ?? $user->name,
            'name' => $user->name,
            'access_token' => $user->token,
            'refresh_token' => $user->refreshToken ?? null,
            'expires_at' => $user->expiresIn ? now()->addSeconds($user->expiresIn) : null,
            'profile_data' => [
                'screen_name' => $user->nickname,
                'profile_image_url' => $user->avatar,
                'description' => $profile['description'] ?? null,
                'followers_count' => $profile['public_metrics']['followers_count'] ?? 0,
                'following_count' => $profile['public_metrics']['following_count'] ?? 0,
                'tweet_count' => $profile['public_metrics']['tweet_count'] ?? 0,
                'verified' => $profile['verified'] ?? false,
            ],
        ];
    }

    /**
     * Refresh access token (Twitter OAuth 2.0 with PKCE)
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = Http::asForm()->post('https://api.twitter.com/2/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => config('services.twitter.client_id'),
        ]);

        if ($response->failed()) {
            throw new \Exception('Error al renovar token de Twitter: '.$response->body());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $refreshToken,
            'expires_at' => now()->addSeconds($data['expires_in']),
        ];
    }

    /**
     * Get Twitter user profile
     */
    public function getUserProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://api.twitter.com/2/users/me', [
                'user.fields' => 'id,name,username,description,profile_image_url,public_metrics,verified',
            ]);

        if ($response->failed()) {
            throw new \Exception('Error al obtener perfil de Twitter: '.$response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Validate Twitter webhook signature (CRC)
     */
    public function validateWebhook(Request $request): bool
    {
        $signature = $request->header('X-Twitter-Webhooks-Signature');

        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256='.base64_encode(
            hash_hmac('sha256', $payload, config('services.twitter.webhook_secret'), true)
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Respond to Twitter CRC challenge
     */
    public function respondToCrcChallenge(string $crcToken): array
    {
        $hash = hash_hmac('sha256', $crcToken, config('services.twitter.webhook_secret'), true);

        return [
            'response_token' => 'sha256='.base64_encode($hash),
        ];
    }
}

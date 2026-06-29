<?php

namespace Modules\Reviews\Services;

use Google\Auth\OAuth2;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Exceptions\ConnectionExpiredException;
use Modules\Reviews\Models\ReviewGoogleConnection;

class GoogleAuthService
{
    private OAuth2 $oauth;

    public function __construct()
    {
        $this->oauth = new OAuth2([
            'clientId' => config('reviews.google.client_id'),
            'clientSecret' => config('reviews.google.client_secret'),
            'authorizationUri' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'tokenCredentialUri' => 'https://oauth2.googleapis.com/token',
            'redirectUri' => config('reviews.google.redirect_uri'),
            'scope' => config('reviews.google.scopes'),
        ]);
    }

    public function getAuthUrl(string $state): string
    {
        return $this->oauth->buildFullAuthorizationUri([
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);
    }

    public function handleCallback(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('reviews.google.client_id'),
            'client_secret' => config('reviews.google.client_secret'),
            'redirect_uri' => config('reviews.google.redirect_uri'),
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            Log::error('Google OAuth token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Google OAuth request failed');
        }

        $data = $response->json();

        if (! isset($data['access_token'])) {
            throw new \RuntimeException('OAuth error: No access token received');
        }

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in' => $data['expires_in'] ?? 3600,
            'scope' => $data['scope'] ?? implode(' ', config('reviews.google.scopes')),
        ];
    }

    public function refreshTokenIfNeeded(ReviewGoogleConnection $connection): void
    {
        if ($connection->token_expires_at && $connection->token_expires_at->isFuture()) {
            return;
        }

        if (! $connection->refresh_token) {
            $connection->markAsExpired('No refresh token available');

            activity()
                ->performedOn($connection)
                ->log('Connection marked as expired: no refresh token available');

            throw new ConnectionExpiredException($connection);
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('reviews.google.client_id'),
            'client_secret' => config('reviews.google.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::error('Google OAuth token refresh failed', [
                'connection_id' => $connection->id,
                'status' => $response->status(),
            ]);
            $connection->markAsExpired('Token refresh failed: HTTP '.$response->status());
            throw new \RuntimeException('Token refresh failed: HTTP '.$response->status());
        }

        $data = $response->json();

        $connection->update([
            'access_token' => $data['access_token'],
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
            'status' => 'active',
            'last_error' => null,
        ]);

        activity()
            ->performedOn($connection)
            ->log('Token refreshed successfully');
    }

    public function revokeToken(ReviewGoogleConnection $connection): void
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/revoke', [
            'token' => $connection->access_token,
        ]);

        if ($response->failed()) {
            Log::error('Google token revocation failed', [
                'connection_id' => $connection->id,
                'status' => $response->status(),
            ]);
            activity()
                ->performedOn($connection)
                ->log('Token revocation failed: HTTP '.$response->status());
            throw new \RuntimeException('Token revocation failed: HTTP '.$response->status());
        }

        $connection->markAsRevoked();

        activity()
            ->performedOn($connection)
            ->log('Token revoked successfully');
    }

    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v2/userinfo');

        if ($response->failed()) {
            Log::error('Failed to fetch Google user info', [
                'status' => $response->status(),
            ]);
            throw new \RuntimeException('Google API request failed');
        }

        return $response->json();
    }
}

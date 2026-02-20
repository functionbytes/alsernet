<?php

namespace Modules\Reviews\Services;

use Google\Auth\OAuth2;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;
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

    /**
     * SECURITY FIX: Create Guzzle client with secure defaults
     * Ensures SSL verification, timeouts, and proper error handling
     */
    private function createSecureClient(): GuzzleClient
    {
        return new GuzzleClient([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => true, // CRITICAL: Verify SSL certificates
            'http_errors' => false, // Handle errors manually for better control
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
        try {
            $client = $this->createSecureClient();

            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'code' => $code,
                    'client_id' => config('reviews.google.client_id'),
                    'client_secret' => config('reviews.google.client_secret'),
                    'redirect_uri' => config('reviews.google.redirect_uri'),
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (! isset($data['access_token'])) {
                throw new \RuntimeException('OAuth error: No access token received');
            }

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'] ?? 3600,
                'scope' => $data['scope'] ?? implode(' ', config('reviews.google.scopes')),
            ];
        } catch (ConnectException $e) {
            Log::error('Connection failed during OAuth callback', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Cannot connect to Google OAuth endpoint');
        } catch (ServerException $e) {
            Log::error('Google OAuth server error', [
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Google OAuth server error');
        } catch (RequestException $e) {
            Log::error('Google OAuth request error', [
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Google OAuth request failed');
        }
    }

    public function refreshTokenIfNeeded(ReviewGoogleConnection $connection): void
    {
        if ($connection->token_expires_at && $connection->token_expires_at->isFuture()) {
            return;
        }

        if (! $connection->refresh_token) {
            throw new \RuntimeException('No refresh token available for connection '.$connection->id);
        }

        try {
            $client = $this->createSecureClient();

            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => config('reviews.google.client_id'),
                    'client_secret' => config('reviews.google.client_secret'),
                    'refresh_token' => $connection->refresh_token,
                    'grant_type' => 'refresh_token',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $connection->update([
                'access_token' => $data['access_token'],
                'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                'status' => 'active',
                'last_error' => null,
            ]);

            activity()
                ->performedOn($connection)
                ->log('Token refreshed successfully');
        } catch (ConnectException $e) {
            Log::error('Connection failed during token refresh', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            $connection->markAsExpired('Connection failed: '.$e->getMessage());
            throw $e;
        } catch (ServerException $e) {
            Log::error('Google OAuth server error during token refresh', [
                'connection_id' => $connection->id,
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            $connection->markAsExpired('Server error: '.$e->getMessage());
            throw $e;
        } catch (RequestException $e) {
            Log::error('Google OAuth request error during token refresh', [
                'connection_id' => $connection->id,
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            $connection->markAsExpired('Request error: '.$e->getMessage());
            throw $e;
        }
    }

    public function revokeToken(ReviewGoogleConnection $connection): void
    {
        try {
            $client = $this->createSecureClient();

            $client->post('https://oauth2.googleapis.com/revoke', [
                'form_params' => [
                    'token' => $connection->access_token,
                ],
            ]);

            $connection->markAsRevoked();

            activity()
                ->performedOn($connection)
                ->log('Token revoked successfully');
        } catch (ConnectException $e) {
            Log::error('Connection failed during token revocation', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            activity()
                ->performedOn($connection)
                ->log('Token revocation failed: Connection error');
            throw $e;
        } catch (ServerException $e) {
            Log::error('Google server error during token revocation', [
                'connection_id' => $connection->id,
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            activity()
                ->performedOn($connection)
                ->log('Token revocation failed: Server error');
            throw $e;
        } catch (RequestException $e) {
            Log::error('Google request error during token revocation', [
                'connection_id' => $connection->id,
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            activity()
                ->performedOn($connection)
                ->log('Token revocation failed: Request error');
            throw $e;
        }
    }

    public function getUserInfo(string $accessToken): array
    {
        try {
            $client = $this->createSecureClient();

            $response = $client->get('https://www.googleapis.com/oauth2/v2/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer '.$accessToken,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ConnectException $e) {
            Log::error('Connection failed while fetching user info', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Cannot connect to Google API');
        } catch (ServerException $e) {
            Log::error('Google server error while fetching user info', [
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Google API server error');
        } catch (RequestException $e) {
            Log::error('Google request error while fetching user info', [
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Google API request failed');
        }
    }
}

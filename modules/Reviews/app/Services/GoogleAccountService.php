<?php

namespace Modules\Reviews\Services;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Reviews\Models\ReviewGoogleConnection;

class GoogleAccountService
{
    public function __construct(
        private GoogleAuthService $authService
    ) {}

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

    /**
     * PERFORMANCE FIX: Rate limiting for Google API calls
     * Prevents exceeding Google API quota (60 requests per minute)
     */
    private function checkRateLimit(string $connectionId): void
    {
        $key = "google-api:{$connectionId}";
        $limit = 60; // Requests per minute
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);
            throw new \RuntimeException("Google API rate limit exceeded. Try again in {$seconds} seconds.");
        }

        RateLimiter::hit($key, $decayMinutes);
    }

    public function listAccounts(ReviewGoogleConnection $connection): array
    {
        $this->checkRateLimit($connection->id);

        $this->authService->refreshTokenIfNeeded($connection);

        try {
            $client = $this->createSecureClient();
            $baseUrl = config('reviews.google.api.account_management');

            $response = $client->get("{$baseUrl}/accounts", [
                'headers' => [
                    'Authorization' => 'Bearer '.$connection->access_token,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['accounts'] ?? [];
        } catch (ConnectException $e) {
            Log::error('Connection failed while listing accounts', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (ServerException $e) {
            Log::error('Google API server error while listing accounts', [
                'connection_id' => $connection->id,
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (RequestException $e) {
            Log::error('Google API request error while listing accounts', [
                'connection_id' => $connection->id,
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getAccount(ReviewGoogleConnection $connection, string $accountId): array
    {
        $this->checkRateLimit($connection->id);

        $this->authService->refreshTokenIfNeeded($connection);

        try {
            $client = $this->createSecureClient();
            $baseUrl = config('reviews.google.api.account_management');

            $response = $client->get("{$baseUrl}/accounts/{$accountId}", [
                'headers' => [
                    'Authorization' => 'Bearer '.$connection->access_token,
                    'Accept' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ConnectException $e) {
            Log::error('Connection failed while getting account', [
                'connection_id' => $connection->id,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (ServerException $e) {
            Log::error('Google API server error while getting account', [
                'connection_id' => $connection->id,
                'account_id' => $accountId,
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (RequestException $e) {
            Log::error('Google API request error while getting account', [
                'connection_id' => $connection->id,
                'account_id' => $accountId,
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

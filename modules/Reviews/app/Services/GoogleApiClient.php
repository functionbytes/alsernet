<?php

namespace Modules\Reviews\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Exceptions\CircuitBreakerOpenException;
use Modules\Reviews\Exceptions\RateLimitExceededException;
use Modules\Reviews\Models\ReviewGoogleConnection;

/**
 * Centralized Google API HTTP client with automatic token refresh,
 * per-account rate limiting, circuit breaker, and comprehensive error handling.
 */
class GoogleApiClient
{
    private const RATE_LIMIT_PER_MINUTE = 100;

    private const RATE_LIMIT_PER_DAY = 10000;

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_MS = 1000;

    public function __construct(
        private readonly GoogleAuthService $authService
    ) {}

    public function get(
        ReviewGoogleConnection $connection,
        string $endpoint,
        array $query = []
    ): array {
        return $this->request($connection, 'GET', $endpoint, [
            'query' => $query,
        ]);
    }

    public function post(
        ReviewGoogleConnection $connection,
        string $endpoint,
        array $data = []
    ): array {
        return $this->request($connection, 'POST', $endpoint, [
            'json' => $data,
        ]);
    }

    public function put(
        ReviewGoogleConnection $connection,
        string $endpoint,
        array $data = []
    ): array {
        return $this->request($connection, 'PUT', $endpoint, [
            'json' => $data,
        ]);
    }

    public function patch(
        ReviewGoogleConnection $connection,
        string $endpoint,
        array $data = []
    ): array {
        return $this->request($connection, 'PATCH', $endpoint, [
            'json' => $data,
        ]);
    }

    public function delete(
        ReviewGoogleConnection $connection,
        string $endpoint
    ): array {
        return $this->request($connection, 'DELETE', $endpoint);
    }

    private function request(
        ReviewGoogleConnection $connection,
        string $method,
        string $endpoint,
        array $options = []
    ): array {
        $this->checkRateLimit($connection->id);

        $breaker = new CircuitBreaker("google-api-{$connection->id}");

        if ($breaker->isOpen()) {
            throw new CircuitBreakerOpenException("google-api-{$connection->id}");
        }

        $this->authService->refreshTokenIfNeeded($connection);

        $attempt = 0;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $client = Http::timeout(30)
                    ->connectTimeout(10)
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$connection->access_token,
                        'Accept' => 'application/json',
                    ]);

                $response = match (strtoupper($method)) {
                    'GET' => $client->get($endpoint, $options['query'] ?? []),
                    'POST' => $client->post($endpoint, $options['json'] ?? []),
                    'PUT' => $client->put($endpoint, $options['json'] ?? []),
                    'PATCH' => $client->patch($endpoint, $options['json'] ?? []),
                    'DELETE' => $client->delete($endpoint),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
                };

                if ($response->failed()) {
                    $this->handleErrorResponse($response, $method, $endpoint, $connection);
                }

                $breaker->recordSuccess();

                activity()
                    ->performedOn($connection)
                    ->withProperties([
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'status_code' => $response->status(),
                    ])
                    ->log('Google API request successful');

                return $response->json() ?? [];
            } catch (ConnectionException $e) {
                $attempt++;

                if ($attempt >= self::MAX_RETRIES) {
                    $breaker->recordFailure();

                    Log::error('Google API connection failed after retries', [
                        'connection_id' => $connection->id,
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'attempts' => $attempt,
                        'error' => $e->getMessage(),
                    ]);

                    throw $e;
                }

                $delay = self::RETRY_DELAY_MS * (2 ** ($attempt - 1));
                usleep($delay * 1000);
            } catch (RequestException $e) {
                $statusCode = $e->response?->status();

                // 401/403 are auth issues — do not count against the circuit
                if (in_array($statusCode, [401, 403], true)) {
                    Log::error('Google API auth error', [
                        'connection_id' => $connection->id,
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'status_code' => $statusCode,
                        'error' => $e->getMessage(),
                    ]);

                    throw $e;
                }

                if ($statusCode >= 500 && $attempt < self::MAX_RETRIES - 1) {
                    $attempt++;
                    $delay = self::RETRY_DELAY_MS * (2 ** ($attempt - 1));
                    usleep($delay * 1000);

                    continue;
                }

                // 5xx error on final attempt — count as circuit failure
                if ($statusCode >= 500) {
                    $breaker->recordFailure();
                }

                Log::error('Google API request error', [
                    'connection_id' => $connection->id,
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status_code' => $statusCode,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        throw new \RuntimeException('Max retry attempts exceeded for Google API request');
    }

    /**
     * Enforce per-minute and per-day rate limits for a given connection.
     *
     * @throws RateLimitExceededException
     */
    private function checkRateLimit(int $connectionId): void
    {
        $minuteKey = "google-api-rate:{$connectionId}";
        $dailyKey = 'google-api-daily:'.$connectionId.':'.now()->format('Y-m-d');

        try {
            // Per-minute check
            $minuteCount = (int) Cache::get($minuteKey, 0);

            if ($minuteCount >= self::RATE_LIMIT_PER_MINUTE) {
                throw new RateLimitExceededException($connectionId, 'per_minute', 60);
            }

            // Per-day check
            $dailyCount = (int) Cache::get($dailyKey, 0);

            if ($dailyCount >= self::RATE_LIMIT_PER_DAY) {
                $secondsUntilMidnight = now()->secondsUntilEndOfDay() + 1;
                throw new RateLimitExceededException($connectionId, 'per_day', $secondsUntilMidnight);
            }

            // Increment both counters atomically
            if ($minuteCount === 0) {
                Cache::put($minuteKey, 1, 60);
            } else {
                Cache::increment($minuteKey);
            }

            if ($dailyCount === 0) {
                Cache::put($dailyKey, 1, now()->secondsUntilEndOfDay() + 1);
            } else {
                Cache::increment($dailyKey);
            }
        } catch (RateLimitExceededException $e) {
            throw $e;
        } catch (\Throwable) {
            // Cache unavailable — allow request through rather than blocking all API calls
        }
    }

    private function handleErrorResponse($response, string $method, string $endpoint, ReviewGoogleConnection $connection): void
    {
        $statusCode = $response->status();
        $body = $response->body();

        Log::error('Google API returned error status', [
            'connection_id' => $connection->id,
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'response_body' => $body,
        ]);

        $errorMessage = "Google API error: HTTP {$statusCode}";

        $data = $response->json();
        if ($data && isset($data['error']['message'])) {
            $errorMessage .= ' - '.$data['error']['message'];
        }

        throw new \RuntimeException($errorMessage);
    }
}

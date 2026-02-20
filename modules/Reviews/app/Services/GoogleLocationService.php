<?php

namespace Modules\Reviews\Services;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;

class GoogleLocationService
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

    public function syncLocations(ReviewGoogleConnection $connection): int
    {
        $this->authService->refreshTokenIfNeeded($connection);

        $accounts = app(GoogleAccountService::class)->listAccounts($connection);
        $syncedCount = 0;

        foreach ($accounts as $account) {
            $accountName = $account['name'] ?? $account['accountNumber'];
            $locations = $this->fetchLocations($connection, $accountName);

            foreach ($locations as $locationData) {
                $this->saveLocation($connection, $locationData);
                $syncedCount++;
            }
        }

        activity()
            ->performedOn($connection)
            ->log("Synced {$syncedCount} locations");

        return $syncedCount;
    }

    public function fetchLocations(ReviewGoogleConnection $connection, string $accountName): array
    {
        try {
            $client = $this->createSecureClient();
            $baseUrl = config('reviews.google.api.business_information');

            $response = $client->get("{$baseUrl}/{$accountName}/locations", [
                'headers' => [
                    'Authorization' => 'Bearer '.$connection->access_token,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'readMask' => 'name,title,phoneNumbers,websiteUri,storefrontAddress,regularHours',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['locations'] ?? [];
        } catch (ConnectException $e) {
            Log::error('Connection failed while fetching locations', [
                'connection_id' => $connection->id,
                'account_name' => $accountName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (ServerException $e) {
            Log::error('Google API server error while fetching locations', [
                'connection_id' => $connection->id,
                'account_name' => $accountName,
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (RequestException $e) {
            Log::error('Google API request error while fetching locations', [
                'connection_id' => $connection->id,
                'account_name' => $accountName,
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function saveLocation(ReviewGoogleConnection $connection, array $locationData): ReviewGoogleLocation
    {
        $googleLocationId = $locationData['name'];

        preg_match('/accounts\/(\d+)\/locations/', $googleLocationId, $matches);
        $googleAccountId = $matches[1] ?? null;

        $address = $this->formatAddress($locationData['storefrontAddress'] ?? []);
        $phone = $locationData['phoneNumbers'][0]['phoneNumber'] ?? null;
        $websiteUrl = $locationData['websiteUri'] ?? null;

        return ReviewGoogleLocation::query()->updateOrCreate(
            [
                'google_location_id' => $googleLocationId,
            ],
            [
                'connection_id' => $connection->id,
                'google_account_id' => $googleAccountId,
                'name' => $locationData['title'] ?? 'Unnamed Location',
                'address' => $address,
                'phone' => $phone,
                'website_url' => $websiteUrl,
                'metadata_json' => $locationData,
                'synced_at' => now(),
            ]
        );
    }

    private function formatAddress(array $address): ?string
    {
        if (empty($address)) {
            return null;
        }

        $parts = array_filter([
            $address['addressLines'][0] ?? null,
            $address['locality'] ?? null,
            $address['administrativeArea'] ?? null,
            $address['postalCode'] ?? null,
            $address['regionCode'] ?? null,
        ]);

        return implode(', ', $parts);
    }

    public function updateLocationStats(ReviewGoogleLocation $location, float $rating, int $totalReviews): void
    {
        $location->updateStats($rating, $totalReviews);

        activity()
            ->performedOn($location)
            ->log("Updated stats: {$rating} avg rating, {$totalReviews} reviews");
    }
}

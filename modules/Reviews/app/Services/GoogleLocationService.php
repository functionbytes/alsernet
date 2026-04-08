<?php

namespace Modules\Reviews\Services;

use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;

class GoogleLocationService
{
    public function __construct(
        private readonly GoogleApiClient $apiClient
    ) {}

    public function syncLocations(ReviewGoogleConnection $connection): int
    {
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
        $baseUrl = config('reviews.google.api.business_information');
        $endpoint = "{$baseUrl}/{$accountName}/locations";

        $data = $this->apiClient->get($connection, $endpoint, [
            'readMask' => 'name,title,phoneNumbers,websiteUri,storefrontAddress,regularHours',
        ]);

        return $data['locations'] ?? [];
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

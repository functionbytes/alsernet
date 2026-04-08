<?php

namespace Modules\Reviews\Services;

use Modules\Reviews\Models\ReviewGoogleConnection;

class GoogleAccountService
{
    public function __construct(
        private readonly GoogleApiClient $apiClient
    ) {}

    public function listAccounts(ReviewGoogleConnection $connection): array
    {
        $baseUrl = config('reviews.google.api.account_management');
        $endpoint = "{$baseUrl}/accounts";

        $data = $this->apiClient->get($connection, $endpoint);

        return $data['accounts'] ?? [];
    }

    public function getAccount(ReviewGoogleConnection $connection, string $accountId): array
    {
        $baseUrl = config('reviews.google.api.account_management');
        $endpoint = "{$baseUrl}/accounts/{$accountId}";

        return $this->apiClient->get($connection, $endpoint);
    }
}

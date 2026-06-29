<?php

namespace Modules\Reviews\Platforms;

use Carbon\Carbon;
use Modules\Reviews\Contracts\ReviewPlatform;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Services\GoogleApiClient;
use Modules\Reviews\Services\GoogleReviewService;

class GoogleReviewPlatform implements ReviewPlatform
{
    public function __construct(
        private readonly GoogleReviewService $reviewService,
        private readonly GoogleApiClient $apiClient,
    ) {}

    public function getName(): string
    {
        return 'google';
    }

    public function getDisplayName(): string
    {
        return 'Google Business Profile';
    }

    public function supportsReplies(): bool
    {
        return true;
    }

    public function supportsSync(): bool
    {
        return true;
    }

    /**
     * Sync reviews for all active locations under the given connection.
     *
     * @param  ReviewGoogleConnection  $connection
     */
    public function syncReviews(mixed $connection, ?Carbon $since): int
    {
        $synced = 0;

        ReviewGoogleLocation::query()
            ->where('connection_id', $connection->id)
            ->active()
            ->each(function (ReviewGoogleLocation $location) use (&$synced) {
                $synced += $this->reviewService->syncReviews($location);
            });

        return $synced;
    }

    public function publishReply(ReviewReply $reply): bool
    {
        return $this->reviewService->publishReply($reply);
    }

    /**
     * @param  ReviewGoogleConnection  $connection
     */
    public function revokeConnection(mixed $connection): bool
    {
        try {
            $baseUrl = config('reviews.google.api.base_url', 'https://oauth2.googleapis.com');
            $this->apiClient->post($connection, "{$baseUrl}/revoke", [
                'token' => $connection->access_token,
            ]);
        } catch (\Exception) {
            // Token may already be invalid — proceed with local revocation
        }

        $connection->markAsRevoked();

        return true;
    }
}

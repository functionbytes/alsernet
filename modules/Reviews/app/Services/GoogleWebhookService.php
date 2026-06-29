<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\ReviewGoogleConnection;

/**
 * Manages Google Cloud Pub/Sub webhook subscriptions for push notifications
 * from the Google Business Profile API.
 *
 * Push notifications arrive via Cloud Pub/Sub and require a registered
 * subscription that points to the webhook endpoint exposed by this application.
 */
class GoogleWebhookService
{
    private const PUBSUB_BASE = 'https://pubsub.googleapis.com/v1';

    public function __construct(
        private readonly GoogleApiClient $apiClient
    ) {}

    /**
     * Register a Pub/Sub push subscription for the given connection.
     *
     * @param  string  $callbackUrl  The public HTTPS URL that Google will POST to.
     */
    public function subscribe(ReviewGoogleConnection $connection, string $callbackUrl): bool
    {
        $projectId = config('reviews.google.pubsub_project_id');
        $topicName = config('reviews.google.pubsub_topic', "projects/{$projectId}/topics/business-profile-reviews");
        $subscriptionId = "reviews-connection-{$connection->id}";
        $subscriptionName = "projects/{$projectId}/subscriptions/{$subscriptionId}";

        $endpoint = self::PUBSUB_BASE."/{$subscriptionName}";

        try {
            $this->apiClient->put($connection, $endpoint, [
                'topic' => $topicName,
                'pushConfig' => [
                    'pushEndpoint' => $callbackUrl,
                    'oidcToken' => [
                        'serviceAccountEmail' => config('reviews.google.pubsub_service_account'),
                    ],
                ],
                'ackDeadlineSeconds' => 30,
            ]);

            activity()
                ->performedOn($connection)
                ->withProperties(['subscription' => $subscriptionName, 'callback_url' => $callbackUrl])
                ->log('Google Pub/Sub subscription created');

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to create Google Pub/Sub subscription', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete the Pub/Sub push subscription for the given connection.
     */
    public function unsubscribe(ReviewGoogleConnection $connection): bool
    {
        $projectId = config('reviews.google.pubsub_project_id');
        $subscriptionId = "reviews-connection-{$connection->id}";
        $subscriptionName = "projects/{$projectId}/subscriptions/{$subscriptionId}";

        $endpoint = self::PUBSUB_BASE."/{$subscriptionName}";

        try {
            $this->apiClient->delete($connection, $endpoint);

            activity()
                ->performedOn($connection)
                ->withProperties(['subscription' => $subscriptionName])
                ->log('Google Pub/Sub subscription deleted');

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to delete Google Pub/Sub subscription', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Retrieve the current subscription details from Google Pub/Sub.
     *
     * Returns null if the subscription does not exist or the request fails.
     *
     * @return array{
     *   name: string,
     *   topic: string,
     *   pushConfig: array,
     *   ackDeadlineSeconds: int
     * }|null
     */
    public function getSubscriptionStatus(ReviewGoogleConnection $connection): ?array
    {
        $projectId = config('reviews.google.pubsub_project_id');
        $subscriptionId = "reviews-connection-{$connection->id}";
        $subscriptionName = "projects/{$projectId}/subscriptions/{$subscriptionId}";

        $endpoint = self::PUBSUB_BASE."/{$subscriptionName}";

        try {
            $response = Http::timeout(10)
                ->withToken($connection->access_token)
                ->get($endpoint);

            if ($response->notFound()) {
                return null;
            }

            $response->throw();

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch Google Pub/Sub subscription status', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

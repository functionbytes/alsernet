<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Jobs\SyncGoogleReviewsJob;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives push notifications from Google Business Profile via Cloud Pub/Sub.
 *
 * Google sends a POST request with a JSON body:
 * {
 *   "message": {
 *     "data": "<base64-encoded JSON>",
 *     "messageId": "...",
 *     "publishTime": "..."
 *   },
 *   "subscription": "projects/.../subscriptions/..."
 * }
 *
 * The decoded `data` payload typically contains:
 * {
 *   "name": "accounts/{accountId}/locations/{locationId}/reviews/{reviewId}",
 *   "type": "REVIEW_NEW" | "REVIEW_UPDATED" | "REPLY_UPDATED"
 * }
 */
class ReviewWebhookController extends Controller
{
    public function handleGoogleNotification(Request $request): Response
    {
        if (! config('reviews.general.webhooks.enabled')) {
            return response('', Response::HTTP_OK);
        }

        $message = $request->input('message');

        if (! is_array($message) || empty($message['data'])) {
            $this->logWebhookEvent('google', 'invalid_payload', null, 'missing_message_data', false);

            // Return 200 so Google stops retrying a malformed message
            return response('', Response::HTTP_OK);
        }

        $decoded = base64_decode($message['data'], strict: true);

        if ($decoded === false) {
            $this->logWebhookEvent('google', 'invalid_payload', null, 'base64_decode_failed', false);

            return response('', Response::HTTP_OK);
        }

        $notification = json_decode($decoded, associative: true);

        if (! is_array($notification)) {
            $this->logWebhookEvent('google', 'invalid_payload', null, 'json_decode_failed', false);

            return response('', Response::HTTP_OK);
        }

        $notificationType = $notification['type'] ?? 'UNKNOWN';
        $resourceName = $notification['name'] ?? '';
        $locationName = $this->extractLocationName($resourceName);

        if (! $locationName) {
            $this->logWebhookEvent('google', $notificationType, null, 'location_not_extracted', false);

            return response('', Response::HTTP_OK);
        }

        $location = ReviewGoogleLocation::query()
            ->where('google_location_id', $locationName)
            ->first();

        if (! $location) {
            $this->logWebhookEvent('google', $notificationType, $locationName, 'location_not_found', false);

            return response('', Response::HTTP_OK);
        }

        SyncGoogleReviewsJob::dispatch($location);

        $this->logWebhookEvent('google', $notificationType, $locationName, 'sync_dispatched', true);

        return response('', Response::HTTP_OK);
    }

    public function verifyGoogleWebhook(Request $request): Response
    {
        $verifyToken = config('reviews.general.webhooks.google_verification_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');
        $receivedToken = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');

        if (! $verifyToken || ! hash_equals($verifyToken, (string) $receivedToken)) {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        if (! $challenge) {
            return response('Bad Request', Response::HTTP_BAD_REQUEST);
        }

        return response((string) $challenge, Response::HTTP_OK);
    }

    /**
     * Extract the location ID from a Google resource name.
     *
     * Resource names follow the pattern:
     *   accounts/{accountId}/locations/{locationId}/reviews/{reviewId}
     *
     * Returns the full location resource name used by the API:
     *   accounts/{accountId}/locations/{locationId}
     */
    private function extractLocationName(string $resourceName): ?string
    {
        if (preg_match('#(accounts/[^/]+/locations/[^/]+)#', $resourceName, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function logWebhookEvent(
        string $source,
        string $eventType,
        ?string $locationName,
        string $status,
        bool $success
    ): void {
        $context = [
            'source' => $source,
            'event_type' => $eventType,
            'location_name' => $locationName,
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($success) {
            Log::info('Webhook received', $context);
        } else {
            Log::warning('Webhook processing issue', $context);
        }

        activity()
            ->withProperties($context)
            ->log("Webhook [{$source}] {$eventType}: {$status}");
    }
}

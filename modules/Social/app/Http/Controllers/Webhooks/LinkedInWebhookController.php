<?php

namespace Modules\Social\Http\Controllers\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Social\Jobs\ProcessLinkedInWebhookJob;
use Modules\Social\Models\SocialAccount;

class LinkedInWebhookController extends BaseWebhookController
{
    /**
     * Verify LinkedIn webhook signature.
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-LinkedIn-Signature');

        if (! $signature) {
            Log::warning('LinkedIn webhook missing signature header');

            return false;
        }

        $webhookSecret = config('services.linkedin.webhook_secret');

        $expectedSignature = base64_encode(
            hash_hmac('sha256', $request->getContent(), $webhookSecret, true)
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle LinkedIn webhook event.
     */
    protected function handleEvent(Request $request): JsonResponse
    {
        $data = $request->all();

        // LinkedIn webhook payload structure
        $events = $data['events'] ?? [];

        foreach ($events as $event) {
            $this->processEvent($event);
        }

        return $this->success();
    }

    /**
     * Process individual LinkedIn event.
     */
    protected function processEvent(array $event): void
    {
        $eventType = $event['eventType'] ?? null;
        $eventData = $event['eventData'] ?? [];

        $this->logEvent('linkedin_event', [
            'event_type' => $eventType,
            'event_data' => $eventData,
        ]);

        // Extract person/organization URN
        $actorUrn = $eventData['actor'] ?? null;

        if (! $actorUrn) {
            Log::warning('LinkedIn webhook: Missing actor URN');

            return;
        }

        // Extract person/org ID from URN (e.g., "urn:li:person:12345")
        preg_match('/urn:li:(person|organization):(.+)/', $actorUrn, $matches);
        $actorId = $matches[2] ?? null;

        if (! $actorId) {
            Log::warning('LinkedIn webhook: Could not extract actor ID', [
                'actor_urn' => $actorUrn,
            ]);

            return;
        }

        // Find social account by LinkedIn ID
        $socialAccount = SocialAccount::where('network', 'linkedin')
            ->where('network_id', $actorId)
            ->first();

        if (! $socialAccount) {
            Log::warning('LinkedIn webhook: Social account not found', [
                'actor_id' => $actorId,
            ]);

            return;
        }

        // Dispatch job to process the webhook asynchronously
        ProcessLinkedInWebhookJob::dispatch($socialAccount, $eventType, $eventData);
    }

    /**
     * Get network name.
     */
    protected function getNetworkName(): string
    {
        return 'linkedin';
    }
}

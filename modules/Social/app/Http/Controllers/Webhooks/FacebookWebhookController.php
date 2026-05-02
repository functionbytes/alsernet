<?php

namespace Modules\Social\Http\Controllers\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Social\Jobs\ProcessFacebookWebhookJob;
use Modules\Social\Models\SocialAccount;

class FacebookWebhookController extends BaseWebhookController
{
    /**
     * Handle Facebook webhook verification (GET) and events (POST).
     */
    public function handle(Request $request): JsonResponse|string
    {
        // Handle verification challenge (GET request)
        if ($request->isMethod('GET')) {
            return $this->handleVerification($request);
        }

        // Handle webhook events (POST request)
        return parent::handle($request);
    }

    /**
     * Handle Facebook verification challenge.
     */
    protected function handleVerification(Request $request): string
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.facebook.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Facebook webhook verification successful');

            return $challenge;
        }

        Log::warning('Facebook webhook verification failed', [
            'mode' => $mode,
            'received_token' => $token,
        ]);

        abort(403, 'Verification failed');
    }

    /**
     * Verify Facebook webhook signature.
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            Log::warning('Facebook webhook missing signature header');

            return false;
        }

        $expectedSignature = 'sha256='.hash_hmac(
            'sha256',
            $request->getContent(),
            config('services.facebook.webhook_secret')
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle Facebook webhook event.
     */
    protected function handleEvent(Request $request): JsonResponse
    {
        $data = $request->all();

        // Facebook sends events in batches
        $entries = $data['entry'] ?? [];

        foreach ($entries as $entry) {
            $pageId = $entry['id'] ?? null;
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $this->processChange($pageId, $change);
            }
        }

        return $this->success('EVENT_RECEIVED');
    }

    /**
     * Process individual change event.
     */
    protected function processChange(?string $pageId, array $change): void
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        $this->logEvent('facebook_change', [
            'page_id' => $pageId,
            'field' => $field,
            'value' => $value,
        ]);

        // Find social account by page ID
        $socialAccount = SocialAccount::where('network', 'facebook')
            ->where('network_id', $pageId)
            ->first();

        if (! $socialAccount) {
            Log::warning('Facebook webhook: Social account not found', [
                'page_id' => $pageId,
            ]);

            return;
        }

        // Dispatch job to process the webhook asynchronously
        ProcessFacebookWebhookJob::dispatch($socialAccount, $field, $value);
    }

    /**
     * Get network name.
     */
    protected function getNetworkName(): string
    {
        return 'facebook';
    }
}

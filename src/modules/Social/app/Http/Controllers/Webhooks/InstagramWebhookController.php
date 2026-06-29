<?php

namespace Modules\Social\Http\Controllers\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Social\Jobs\ProcessInstagramWebhookJob;
use Modules\Social\Models\SocialAccount;

class InstagramWebhookController extends BaseWebhookController
{
    /**
     * Handle Instagram webhook verification (GET) and events (POST).
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
     * Handle Instagram verification challenge.
     * Instagram uses the same verification as Facebook.
     */
    protected function handleVerification(Request $request): string
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.facebook.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Instagram webhook verification successful');

            return $challenge;
        }

        Log::warning('Instagram webhook verification failed', [
            'mode' => $mode,
            'received_token' => $token,
        ]);

        abort(403, 'Verification failed');
    }

    /**
     * Verify Instagram webhook signature.
     * Instagram uses the same signature as Facebook.
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            Log::warning('Instagram webhook missing signature header');

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
     * Handle Instagram webhook event.
     */
    protected function handleEvent(Request $request): JsonResponse
    {
        $data = $request->all();

        // Instagram sends events in the same format as Facebook
        $entries = $data['entry'] ?? [];

        foreach ($entries as $entry) {
            $igAccountId = $entry['id'] ?? null;
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $this->processChange($igAccountId, $change);
            }
        }

        return $this->success('EVENT_RECEIVED');
    }

    /**
     * Process individual change event.
     */
    protected function processChange(?string $igAccountId, array $change): void
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        $this->logEvent('instagram_change', [
            'ig_account_id' => $igAccountId,
            'field' => $field,
            'value' => $value,
        ]);

        // Find social account by Instagram account ID
        $socialAccount = SocialAccount::where('network', 'instagram')
            ->where('network_id', $igAccountId)
            ->first();

        if (! $socialAccount) {
            Log::warning('Instagram webhook: Social account not found', [
                'ig_account_id' => $igAccountId,
            ]);

            return;
        }

        // Dispatch job to process the webhook asynchronously
        ProcessInstagramWebhookJob::dispatch($socialAccount, $field, $value);
    }

    /**
     * Get network name.
     */
    protected function getNetworkName(): string
    {
        return 'instagram';
    }
}

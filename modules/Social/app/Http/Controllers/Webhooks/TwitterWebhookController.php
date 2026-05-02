<?php

namespace Modules\Social\Http\Controllers\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Social\Jobs\ProcessTwitterWebhookJob;
use Modules\Social\Models\SocialAccount;

class TwitterWebhookController extends BaseWebhookController
{
    /**
     * Handle Twitter webhook (GET for CRC, POST for events).
     */
    public function handle(Request $request): JsonResponse
    {
        // Handle CRC challenge (GET request)
        if ($request->isMethod('GET')) {
            return $this->handleCrcChallenge($request);
        }

        // Handle webhook events (POST request)
        return parent::handle($request);
    }

    /**
     * Handle Twitter CRC (Challenge-Response Check).
     */
    protected function handleCrcChallenge(Request $request): JsonResponse
    {
        $crcToken = $request->query('crc_token');

        if (! $crcToken) {
            return $this->error('Missing crc_token', [], 400);
        }

        $consumerSecret = config('services.twitter.consumer_secret');

        // Calculate response hash
        $responseToken = 'sha256='.base64_encode(
            hash_hmac('sha256', $crcToken, $consumerSecret, true)
        );

        Log::info('Twitter CRC challenge successful');

        return response()->json([
            'response_token' => $responseToken,
        ]);
    }

    /**
     * Verify Twitter webhook signature.
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Twitter-Webhooks-Signature');

        if (! $signature) {
            Log::warning('Twitter webhook missing signature header');

            return false;
        }

        $consumerSecret = config('services.twitter.consumer_secret');

        $expectedSignature = 'sha256='.base64_encode(
            hash_hmac('sha256', $request->getContent(), $consumerSecret, true)
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle Twitter webhook event.
     */
    protected function handleEvent(Request $request): JsonResponse
    {
        $data = $request->all();

        // Twitter sends different event types
        $userId = $data['for_user_id'] ?? null;

        // Process different event types
        $this->processEvents($userId, $data);

        return $this->success();
    }

    /**
     * Process Twitter events.
     */
    protected function processEvents(?string $userId, array $data): void
    {
        // Find social account by user ID
        $socialAccount = null;
        if ($userId) {
            $socialAccount = SocialAccount::where('network', 'twitter')
                ->where('network_id', $userId)
                ->first();
        }

        // Tweet create events
        if (isset($data['tweet_create_events'])) {
            foreach ($data['tweet_create_events'] as $tweet) {
                $this->logEvent('tweet_create', [
                    'user_id' => $userId,
                    'tweet_id' => $tweet['id_str'] ?? null,
                ]);

                if ($socialAccount) {
                    ProcessTwitterWebhookJob::dispatch($socialAccount, 'tweet_create', $tweet);
                }
            }
        }

        // Favorite events
        if (isset($data['favorite_events'])) {
            foreach ($data['favorite_events'] as $favorite) {
                $this->logEvent('favorite', [
                    'user_id' => $userId,
                    'tweet_id' => $favorite['favorited_status']['id_str'] ?? null,
                ]);

                if ($socialAccount) {
                    ProcessTwitterWebhookJob::dispatch($socialAccount, 'favorite', $favorite);
                }
            }
        }

        // Direct message events
        if (isset($data['direct_message_events'])) {
            foreach ($data['direct_message_events'] as $dm) {
                $this->logEvent('direct_message', [
                    'user_id' => $userId,
                    'message_id' => $dm['id'] ?? null,
                ]);

                if ($socialAccount) {
                    ProcessTwitterWebhookJob::dispatch($socialAccount, 'direct_message', $dm);
                }
            }
        }

        // Tweet delete events
        if (isset($data['tweet_delete_events'])) {
            foreach ($data['tweet_delete_events'] as $delete) {
                $this->logEvent('tweet_delete', [
                    'user_id' => $userId,
                    'tweet_id' => $delete['status']['id_str'] ?? null,
                ]);

                if ($socialAccount) {
                    ProcessTwitterWebhookJob::dispatch($socialAccount, 'tweet_delete', $delete);
                }
            }
        }
    }

    /**
     * Get network name.
     */
    protected function getNetworkName(): string
    {
        return 'twitter';
    }
}

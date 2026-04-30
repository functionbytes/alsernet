<?php

namespace Modules\Chat\Http\Controllers\Api\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Jobs\Webhooks\ProcessInstagramMessageJob;
use Modules\Chat\Models\Channels\Instagram;

class InstagramController extends Controller
{
    /**
     * Verify Instagram webhook subscription.
     *
     * Handles webhook verification challenge from Meta (Facebook/Instagram).
     * Similar verification flow to Facebook webhooks.
     *
     * @param  Request  $request  The webhook verification request
     * @param  string|null  $instagramId  Optional Instagram account ID for specific verification
     * @return Response Plain text response with challenge on success, 400/403 on failure
     *
     * @route GET /api/webhooks/instagram/{instagramId?}
     */
    public function verify(Request $request, ?string $instagramId = null): Response
    {
        $mode = $request->get('hub_mode');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        // Debug logging
        file_put_contents(storage_path('logs/instagram-debug-'.date('Y-m-d').'.log'),
            date('Y-m-d H:i:s')." | VERIFY REQUEST: mode=$mode token=$token challenge=$challenge\n", FILE_APPEND);

        // Validate required parameters
        if (! $mode || ! $token || ! $challenge) {
            file_put_contents(storage_path('logs/instagram-debug-'.date('Y-m-d').'.log'),
                date('Y-m-d H:i:s')." | MISSING PARAMETERS\n", FILE_APPEND);
            Log::warning('Instagram webhook verification failed: missing parameters');

            return response('Missing parameters', 400);
        }

        // Validate mode is 'subscribe'
        if ($mode !== 'subscribe') {
            Log::warning('Instagram webhook verification failed: invalid mode', ['mode' => $mode]);

            return response('Invalid mode', 403);
        }

        // Validate verify token
        if ($instagramId) {
            $instagram = Instagram::where('instagram_id', $instagramId)->first();
            file_put_contents(storage_path('logs/instagram-debug-'.date('Y-m-d').'.log'),
                date('Y-m-d H:i:s')." | ACCOUNT VERIFICATION: instagram_id=$instagramId found=".($instagram ? 'yes' : 'no')."\n", FILE_APPEND);

            if (! $instagram || $instagram->webhook_verify_token !== $token) {
                file_put_contents(storage_path('logs/instagram-debug-'.date('Y-m-d').'.log'),
                    date('Y-m-d H:i:s')." | TOKEN MISMATCH: expected={$instagram->webhook_verify_token} got=$token\n", FILE_APPEND);
                Log::warning('Instagram webhook verification failed: invalid token', [
                    'instagram_id' => $instagramId,
                    'token' => $token,
                ]);

                return response('Invalid verify token', 403);
            }
        } else {
            // Global verification
            $expectedToken = config('channels.instagram.verify_token', env('INSTAGRAM_VERIFY_TOKEN'));
            file_put_contents(storage_path('logs/instagram-debug-'.date('Y-m-d').'.log'),
                date('Y-m-d H:i:s')." | GLOBAL VERIFICATION: expected=$expectedToken got=$token match=".($token === $expectedToken ? 'yes' : 'no')."\n", FILE_APPEND);

            if ($token !== $expectedToken) {
                file_put_contents(storage_path('logs/instagram-debug-'.date('Y-m-d').'.log'),
                    date('Y-m-d H:i:s')." | TOKEN MISMATCH - FAILED\n", FILE_APPEND);
                Log::warning('Instagram webhook verification failed: global token mismatch');

                return response('Invalid verify token', 403);
            }
        }

        // Return the challenge
        file_put_contents(storage_path('logs/instagram-debug-'.date('Y-m-d').'.log'),
            date('Y-m-d H:i:s')." | ✅ VERIFICATION SUCCESS - returning challenge\n", FILE_APPEND);
        Log::info('Instagram webhook verified successfully', ['instagram_id' => $instagramId]);

        return response($challenge, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Handle incoming Instagram webhook events.
     *
     * Processes messages, read receipts, reactions, comments, and mentions.
     * Verifies signature using same method as Facebook (Meta shared system).
     * Dispatches ProcessInstagramMessageJob for async message handling.
     *
     * @param  Request  $request  The incoming webhook payload
     * @param  string|null  $instagramId  Optional Instagram account ID for routing
     * @return JsonResponse Always returns 200 OK status
     *
     * @route POST /api/webhooks/instagram/{instagramId?}
     */
    public function handle(Request $request, ?string $instagramId = null): JsonResponse
    {
        // Log IMMEDIATELY - before any processing
        file_put_contents(storage_path('logs/instagram-debug.log'),
            date('Y-m-d H:i:s')." | === WEBHOOK RECEIVED ===\n", FILE_APPEND);
        file_put_contents(storage_path('logs/instagram-debug.log'),
            date('Y-m-d H:i:s').' | Signature: '.$request->header('X-Hub-Signature-256')."\n", FILE_APPEND);

        try {
            // Log full request like Facebook does
            Log::channel('instagram_debug')->info('=== INSTAGRAM WEBHOOK RECEIVED ===', [
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'headers' => $request->headers->all(),
                'full_payload' => $request->json()->all(),
            ]);

            // Verify webhook signature (Instagram uses same signature as Facebook)
            if (! $this->verifySignature($request)) {
                file_put_contents(storage_path('logs/instagram-debug.log'),
                    date('Y-m-d H:i:s')." | SIGNATURE VERIFICATION FAILED\n", FILE_APPEND);
                Log::channel('instagram_debug')->warning('SIGNATURE VERIFICATION FAILED');
                Log::warning('Instagram webhook signature verification failed');

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            file_put_contents(storage_path('logs/instagram-debug.log'),
                date('Y-m-d H:i:s')." | SIGNATURE VERIFICATION PASSED\n", FILE_APPEND);
            Log::channel('instagram_debug')->info('SIGNATURE VERIFICATION PASSED');

            $data = $request->all();

            // Validate object type
            if (! isset($data['object']) || $data['object'] !== 'instagram') {
                Log::warning('Instagram webhook invalid object type', ['object' => $data['object'] ?? null]);

                return response()->json(['status' => 'ok']);
            }

            // Process each entry
            $instagramAccountsCache = [];

            foreach ($data['entry'] ?? [] as $entry) {
                $instagramId = $entry['id'] ?? null;

                // Find Instagram channel (cache to avoid duplicate queries in batch webhooks)
                if (! isset($instagramAccountsCache[$instagramId])) {
                    $instagramAccountsCache[$instagramId] = Instagram::with('inbox')->where('instagram_id', $instagramId)->first();
                }

                $instagram = $instagramAccountsCache[$instagramId];

                if (! $instagram) {
                    Log::warning('Instagram account not found', ['instagram_id' => $instagramId]);

                    continue;
                }

                // Group messaging events by sender for batch processing
                $messagesBySender = [];
                foreach ($entry['messaging'] ?? [] as $messagingEvent) {
                    if (isset($messagingEvent['message'])) {
                        $senderId = $messagingEvent['sender']['id'] ?? null;
                        if ($senderId) {
                            $messagesBySender[$senderId][] = $messagingEvent;
                        }
                    } else {
                        // Non-message events (read, delivery, reaction) - process individually
                        $this->processNonMessageEvent($messagingEvent);
                    }
                }

                // Dispatch batch jobs for each sender
                foreach ($messagesBySender as $senderId => $senderMessages) {
                    if (count($senderMessages) === 1) {
                        // Single message - dispatch as single event
                        ProcessInstagramMessageJob::dispatch($instagram, $senderMessages[0]);
                    } else {
                        // Multiple messages from same sender - dispatch as batch
                        ProcessInstagramMessageJob::dispatch($instagram, $senderMessages);
                    }

                    Log::channel('instagram_debug')->info('Dispatched batch job', [
                        'sender_id' => $senderId,
                        'message_count' => count($senderMessages),
                    ]);
                }

                // Process changes (feed, comments, mentions)
                foreach ($entry['changes'] ?? [] as $change) {
                    $this->processChange($instagram, $change);
                }
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Instagram webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'ok']); // Always return 200
        }
    }

    /**
     * Verify Instagram webhook signature.
     *
     * Uses HMAC-SHA256 with app secret (same system as Facebook).
     *
     * @param  Request  $request  The webhook request
     * @return bool True if signature matches, false otherwise
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        $appSecret = config('channels.instagram.app_secret', env('INSTAGRAM_APP_SECRET'));
        $payload = $request->getContent();
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $appSecret);
        $match = hash_equals($expectedSignature, $signature);

        // Log complete signature verification
        Log::channel('instagram_debug')->info('SIGNATURE VERIFICATION DETAILS', [
            'received_signature' => $signature,
            'expected_signature' => $expectedSignature,
            'app_secret_preview' => substr($appSecret, 0, 8).'***',
            'payload_size' => strlen($payload),
            'match' => $match,
        ]);

        if (! $signature) {
            return false;
        }

        if (! $appSecret) {
            Log::warning('Instagram app secret not configured');

            return false;
        }

        return $match;
    }

    /**
     * Process non-message Instagram events.
     *
     * Handles read receipts, delivery confirmations, and reactions.
     * Called for events that don't contain messages (no batching needed).
     *
     * @param  array  $event  The messaging event data
     */
    protected function processNonMessageEvent(array $event): void
    {
        if (isset($event['read'])) {
            Log::info('Instagram message read', ['event' => $event]);
        }

        if (isset($event['delivery'])) {
            Log::info('Instagram message delivered', ['event' => $event]);
        }

        if (isset($event['reaction'])) {
            Log::info('Instagram message reaction', ['event' => $event]);
        }
    }

    /**
     * Process an Instagram page change event.
     *
     * Routes different change types (comments, mentions, story_insights) to handlers.
     * Currently logs and delegates to type-specific handlers.
     *
     * @param  Instagram  $instagram  The Instagram account channel
     * @param  array  $change  The change event data with 'field' and 'value' keys
     */
    protected function processChange(Instagram $instagram, array $change): void
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        Log::info('Instagram change', [
            'instagram_id' => $instagram->instagram_id,
            'field' => $field,
            'value' => $value,
        ]);

        // Handle different change types
        match ($field) {
            'comments' => $this->handleCommentChange($instagram, $value),
            'mentions' => $this->handleMentionChange($instagram, $value),
            'story_insights' => $this->handleStoryChange($instagram, $value),
            default => null,
        };
    }

    /**
     * Handle Instagram comment change events.
     *
     * Processes comment updates on posts and stories.
     * Currently logs events - future implementation can create conversations.
     *
     * @param  Instagram  $instagram  The Instagram account channel
     * @param  array  $value  The comment change value data
     */
    protected function handleCommentChange(Instagram $instagram, array $value): void
    {
        Log::info('Instagram comment', ['instagram_id' => $instagram->instagram_id, 'value' => $value]);
    }

    /**
     * Handle Instagram mention change events.
     *
     * Processes mentions of the account in comments or posts.
     * Currently logs events - future implementation can create conversations.
     *
     * @param  Instagram  $instagram  The Instagram account channel
     * @param  array  $value  The mention change value data
     */
    protected function handleMentionChange(Instagram $instagram, array $value): void
    {
        Log::info('Instagram mention', ['instagram_id' => $instagram->instagram_id, 'value' => $value]);
    }

    /**
     * Handle Instagram story insights change events.
     *
     * Processes analytics updates for Instagram stories.
     * Currently logs events - future implementation can surface insights.
     *
     * @param  Instagram  $instagram  The Instagram account channel
     * @param  array  $value  The story insights change value data
     */
    protected function handleStoryChange(Instagram $instagram, array $value): void
    {
        Log::info('Instagram story', ['instagram_id' => $instagram->instagram_id, 'value' => $value]);
    }
}

<?php

namespace Modules\Chat\Http\Controllers\Api\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Jobs\Webhooks\ProcessFacebookMessageJob;
use Modules\Chat\Models\Channels\Facebook;

class FacebookController extends Controller
{
    /**
     * Verify Facebook webhook subscription.
     *
     * Handles the webhook verification challenge from Meta (Facebook).
     * Validates the hub_verify_token and returns the challenge if valid.
     * Can verify against page-specific token or global config.
     *
     * @param  Request  $request  The webhook verification request
     * @param  string|null  $pageId  Optional Facebook page ID for specific page verification
     * @return Response Plain text response with challenge on success, 400/403 on failure
     *
     * @route GET /api/webhooks/facebook/{pageId?}
     */
    public function verify(Request $request, ?string $pageId = null): Response
    {
        $mode = $request->get('hub_mode');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        // Validate required parameters
        if (! $mode || ! $token || ! $challenge) {
            Log::warning('Facebook webhook verification failed: missing parameters');

            return response('Missing parameters', 400);
        }

        // Validate mode is 'subscribe'
        if ($mode !== 'subscribe') {
            Log::warning('Facebook webhook verification failed: invalid mode', ['mode' => $mode]);

            return response('Invalid mode', 403);
        }

        // Validate verify token
        if ($pageId) {
            $facebookPage = Facebook::where('page_id', $pageId)->first();

            if (! $facebookPage || $facebookPage->webhook_verify_token !== $token) {
                Log::warning('Facebook webhook verification failed: invalid token', [
                    'page_id' => $pageId,
                    'token' => $token,
                ]);

                return response('Invalid verify token', 403);
            }
        } else {
            // Global verification - check against config
            $expectedToken = config('channels.facebook.verify_token', env('FACEBOOK_VERIFY_TOKEN'));

            if ($token !== $expectedToken) {
                Log::warning('Facebook webhook verification failed: global token mismatch', [
                    'expected' => $expectedToken,
                    'received' => $token,
                ]);

                return response('Invalid verify token', 403);
            }
        }

        // Return the challenge to complete verification
        Log::info('Facebook webhook verified successfully', ['page_id' => $pageId]);

        return response($challenge, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Handle incoming Facebook webhook events.
     *
     * Processes messages, read receipts, delivery confirmations, and postbacks.
     * Verifies webhook signature before processing. Dispatches ProcessFacebookMessageJob
     * for async message handling. Always returns 200 to Meta to acknowledge receipt.
     *
     * @param  Request  $request  The incoming webhook payload
     * @param  string|null  $pageId  Optional Facebook page ID for routing
     * @return JsonResponse Always returns 200 OK status
     *
     * @route POST /api/webhooks/facebook/{pageId?}
     */
    public function handle(Request $request, ?string $pageId = null): JsonResponse
    {
        try {
            // LOG EVERYTHING FACEBOOK SENDS
            $fullPayload = $request->all();
            Log::channel('facebook_debug')->info('=== FACEBOOK WEBHOOK RECEIVED ===', [
                'timestamp' => now()->toDateTimeString(),
                'headers' => $request->headers->all(),
                'full_payload' => $fullPayload,
                'raw_body' => $request->getContent(),
            ]);

            // Verify webhook signature
            if (! $this->verifySignature($request)) {
                Log::warning('Facebook webhook signature verification failed');
                Log::channel('facebook_debug')->error('Signature verification failed');

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $data = $request->all();

            // Validate object type
            if (! isset($data['object']) || $data['object'] !== 'page') {
                Log::warning('Facebook webhook invalid object type', ['object' => $data['object'] ?? null]);
                Log::channel('facebook_debug')->warning('Invalid object type', ['object' => $data['object'] ?? null]);

                return response()->json(['status' => 'ok']);
            }

            // Process each entry
            $facebookPagesCache = [];

            foreach ($data['entry'] ?? [] as $entry) {
                $pageId = $entry['id'] ?? null;
                Log::channel('facebook_debug')->info('Processing entry', ['page_id' => $pageId, 'entry' => $entry]);

                // Find Facebook page channel (cache to avoid duplicate queries in batch webhooks)
                if (! isset($facebookPagesCache[$pageId])) {
                    $facebookPagesCache[$pageId] = Facebook::with('inbox')->where('page_id', $pageId)->first();
                }

                $facebookPage = $facebookPagesCache[$pageId];

                if (! $facebookPage) {
                    Log::warning('Facebook page not found', ['page_id' => $pageId]);
                    Log::channel('facebook_debug')->error('Facebook page not found', ['page_id' => $pageId]);

                    continue;
                }

                // Group messaging events by sender for batch processing
                $messagesBySender = [];
                foreach ($entry['messaging'] ?? [] as $messagingEvent) {
                    Log::channel('facebook_debug')->info('Messaging event', ['event' => $messagingEvent]);

                    if (isset($messagingEvent['message'])) {
                        $senderId = $messagingEvent['sender']['id'] ?? null;
                        if ($senderId) {
                            $messagesBySender[$senderId][] = $messagingEvent;
                        }
                    } else {
                        // Non-message events (read, delivery, postback) - process individually
                        $this->processNonMessageEvent($messagingEvent);
                    }
                }

                // Dispatch batch jobs for each sender
                foreach ($messagesBySender as $senderId => $senderMessages) {
                    if (count($senderMessages) === 1) {
                        // Single message - dispatch as single event
                        ProcessFacebookMessageJob::dispatch($facebookPage, $senderMessages[0]);
                    } else {
                        // Multiple messages from same sender - dispatch as batch
                        ProcessFacebookMessageJob::dispatch($facebookPage, $senderMessages);
                    }

                    Log::channel('facebook_debug')->info('Dispatched batch job', [
                        'sender_id' => $senderId,
                        'message_count' => count($senderMessages),
                    ]);
                }

                // Process changes (page events)
                foreach ($entry['changes'] ?? [] as $change) {
                    Log::channel('facebook_debug')->info('Change event', ['change' => $change]);
                    $this->processChange($facebookPage, $change);
                }
            }

            Log::channel('facebook_debug')->info('=== WEBHOOK PROCESSED SUCCESSFULLY ===');

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Facebook webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Log::channel('facebook_debug')->error('Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'ok']); // Always return 200 to Facebook
        }
    }

    /**
     * Verify the webhook request signature using HMAC-SHA256.
     *
     * Compares the X-Hub-Signature-256 header against a computed signature of
     * the request body using the app secret. Uses hash_equals for constant-time comparison.
     *
     * @param  Request  $request  The incoming webhook request
     * @return bool True if signature is valid, false otherwise
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $appSecret = config('channels.facebook.app_secret', env('FACEBOOK_APP_SECRET'));

        if (! $appSecret) {
            Log::warning('Facebook app secret not configured');

            return false;
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process non-message Facebook events.
     *
     * Handles read receipts, delivery confirmations, and postback events.
     * Called for events that don't contain messages (no batching needed).
     *
     * @param  array  $event  The messaging event data
     */
    protected function processNonMessageEvent(array $event): void
    {
        if (isset($event['read'])) {
            Log::info('Facebook message read', ['event' => $event]);
        }

        if (isset($event['delivery'])) {
            Log::info('Facebook message delivered', ['event' => $event]);
        }

        if (isset($event['postback'])) {
            Log::info('Facebook postback received', ['event' => $event]);
        }
    }

    /**
     * Process a Facebook page change event.
     *
     * Routes different change types (feed, comments, mentions) to specific handlers.
     * Currently logs and delegates to type-specific handlers.
     *
     * @param  FacebookPage  $facebookPage  The Facebook page channel
     * @param  array  $change  The change event data with 'field' and 'value' keys
     */
    protected function processChange(Facebook $facebookPage, array $change): void
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        Log::info('Facebook page change', [
            'page_id' => $facebookPage->page_id,
            'field' => $field,
            'value' => $value,
        ]);

        // Handle different change types
        match ($field) {
            'feed' => $this->handleFeedChange($facebookPage, $value),
            'comments' => $this->handleCommentChange($facebookPage, $value),
            default => null,
        };
    }

    /**
     * Handle Facebook feed change events.
     *
     * Processes page feed updates including posts and mentions.
     * Currently logs events - future implementation can create conversations.
     *
     * @param  FacebookPage  $facebookPage  The Facebook page channel
     * @param  array  $value  The feed change value data
     */
    protected function handleFeedChange(Facebook $facebookPage, array $value): void
    {
        // Process feed posts, mentions, etc.
        Log::info('Facebook feed change', ['page_id' => $facebookPage->page_id, 'value' => $value]);
    }

    /**
     * Handle Facebook comment change events.
     *
     * Processes comment updates on page posts.
     * Currently logs events - future implementation can create conversations.
     *
     * @param  Facebook  $facebookPage  The Facebook page channel
     * @param  array  $value  The comment change value data
     */
    protected function handleCommentChange(Facebook $facebookPage, array $value): void
    {
        // Process comments on posts
        Log::info('Facebook comment change', ['page_id' => $facebookPage->page_id, 'value' => $value]);
    }
}

<?php

namespace Modules\HelpdeskChat\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Jobs\Webhooks\ProcessFacebookMessageJob;
use Modules\HelpdeskChat\Models\Channels\FacebookPage;

class FacebookController extends Controller
{
    /**
     * Verify Facebook webhook.
     *
     * GET /api/webhooks/facebook/{pageId}
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
            $facebookPage = FacebookPage::where('page_id', $pageId)->first();

            if (! $facebookPage || $facebookPage->webhook_verify_token !== $token) {
                Log::warning('Facebook webhook verification failed: invalid token', [
                    'page_id' => $pageId,
                    'token' => $token,
                ]);

                return response('Invalid verify token', 403);
            }
        } else {
            // Global verification - check against config
            $expectedToken = config('services.facebook.webhook_verify_token', env('FACEBOOK_WEBHOOK_VERIFY_TOKEN'));

            if ($token !== $expectedToken) {
                Log::warning('Facebook webhook verification failed: global token mismatch');

                return response('Invalid verify token', 403);
            }
        }

        // Return the challenge to complete verification
        Log::info('Facebook webhook verified successfully', ['page_id' => $pageId]);

        return response($challenge, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Handle Facebook webhook events.
     *
     * POST /api/webhooks/facebook/{pageId?}
     */
    public function handle(Request $request, ?string $pageId = null): JsonResponse
    {
        try {
            // Verify webhook signature
            if (! $this->verifySignature($request)) {
                Log::warning('Facebook webhook signature verification failed');

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $data = $request->all();

            // Validate object type
            if (! isset($data['object']) || $data['object'] !== 'page') {
                Log::warning('Facebook webhook invalid object type', ['object' => $data['object'] ?? null]);

                return response()->json(['status' => 'ok']);
            }

            // Process each entry
            foreach ($data['entry'] ?? [] as $entry) {
                $pageId = $entry['id'] ?? null;

                // Find Facebook page channel
                $facebookPage = FacebookPage::where('page_id', $pageId)->first();

                if (! $facebookPage) {
                    Log::warning('Facebook page not found', ['page_id' => $pageId]);

                    continue;
                }

                // Process messaging events
                foreach ($entry['messaging'] ?? [] as $messagingEvent) {
                    $this->processMessagingEvent($facebookPage, $messagingEvent);
                }

                // Process changes (page events)
                foreach ($entry['changes'] ?? [] as $change) {
                    $this->processChange($facebookPage, $change);
                }
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Facebook webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'ok']); // Always return 200 to Facebook
        }
    }

    /**
     * Verify webhook signature.
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $appSecret = config('services.facebook.app_secret', env('FACEBOOK_APP_SECRET'));

        if (! $appSecret) {
            Log::warning('Facebook app secret not configured');

            return false;
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process messaging event.
     */
    protected function processMessagingEvent(FacebookPage $facebookPage, array $event): void
    {
        // Dispatch job to process message asynchronously
        if (isset($event['message'])) {
            ProcessFacebookMessageJob::dispatch($facebookPage, $event);
        }

        // Handle other event types
        if (isset($event['read'])) {
            // Message read event
            Log::info('Facebook message read', ['event' => $event]);
        }

        if (isset($event['delivery'])) {
            // Message delivery event
            Log::info('Facebook message delivered', ['event' => $event]);
        }

        if (isset($event['postback'])) {
            // Postback button click
            Log::info('Facebook postback received', ['event' => $event]);
        }
    }

    /**
     * Process page change event.
     */
    protected function processChange(FacebookPage $facebookPage, array $change): void
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
     * Handle feed change.
     */
    protected function handleFeedChange(FacebookPage $facebookPage, array $value): void
    {
        // Process feed posts, mentions, etc.
        Log::info('Facebook feed change', ['page_id' => $facebookPage->page_id, 'value' => $value]);
    }

    /**
     * Handle comment change.
     */
    protected function handleCommentChange(FacebookPage $facebookPage, array $value): void
    {
        // Process comments on posts
        Log::info('Facebook comment change', ['page_id' => $facebookPage->page_id, 'value' => $value]);
    }
}

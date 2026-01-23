<?php

namespace Modules\HelpdeskChat\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Jobs\Webhooks\ProcessInstagramMessageJob;
use Modules\HelpdeskChat\Models\Channels\Instagram;

class InstagramController extends Controller
{
    /**
     * Verify Instagram webhook.
     *
     * GET /api/webhooks/instagram/{instagramId?}
     */
    public function verify(Request $request, ?string $instagramId = null): Response
    {
        $mode = $request->get('hub_mode');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        // Validate required parameters
        if (! $mode || ! $token || ! $challenge) {
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

            if (! $instagram || $instagram->webhook_verify_token !== $token) {
                Log::warning('Instagram webhook verification failed: invalid token', [
                    'instagram_id' => $instagramId,
                    'token' => $token,
                ]);

                return response('Invalid verify token', 403);
            }
        } else {
            // Global verification
            $expectedToken = config('services.instagram.webhook_verify_token', env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN'));

            if ($token !== $expectedToken) {
                Log::warning('Instagram webhook verification failed: global token mismatch');

                return response('Invalid verify token', 403);
            }
        }

        // Return the challenge
        Log::info('Instagram webhook verified successfully', ['instagram_id' => $instagramId]);

        return response($challenge, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Handle Instagram webhook events.
     *
     * POST /api/webhooks/instagram/{instagramId?}
     */
    public function handle(Request $request, ?string $instagramId = null): JsonResponse
    {
        try {
            // Verify webhook signature (Instagram uses same signature as Facebook)
            if (! $this->verifySignature($request)) {
                Log::warning('Instagram webhook signature verification failed');

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $data = $request->all();

            // Validate object type
            if (! isset($data['object']) || $data['object'] !== 'instagram') {
                Log::warning('Instagram webhook invalid object type', ['object' => $data['object'] ?? null]);

                return response()->json(['status' => 'ok']);
            }

            // Process each entry
            foreach ($data['entry'] ?? [] as $entry) {
                $instagramId = $entry['id'] ?? null;

                // Find Instagram channel
                $instagram = Instagram::where('instagram_id', $instagramId)->first();

                if (! $instagram) {
                    Log::warning('Instagram account not found', ['instagram_id' => $instagramId]);

                    continue;
                }

                // Process messaging events
                foreach ($entry['messaging'] ?? [] as $messagingEvent) {
                    $this->processMessagingEvent($instagram, $messagingEvent);
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
     * Verify webhook signature.
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $appSecret = config('services.instagram.app_secret', env('INSTAGRAM_APP_SECRET'));

        if (! $appSecret) {
            Log::warning('Instagram app secret not configured');

            return false;
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process messaging event.
     */
    protected function processMessagingEvent(Instagram $instagram, array $event): void
    {
        // Dispatch job to process message asynchronously
        if (isset($event['message'])) {
            ProcessInstagramMessageJob::dispatch($instagram, $event);
        }

        // Handle other event types
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
     * Process change event.
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
     * Handle comment change.
     */
    protected function handleCommentChange(Instagram $instagram, array $value): void
    {
        Log::info('Instagram comment', ['instagram_id' => $instagram->instagram_id, 'value' => $value]);
    }

    /**
     * Handle mention change.
     */
    protected function handleMentionChange(Instagram $instagram, array $value): void
    {
        Log::info('Instagram mention', ['instagram_id' => $instagram->instagram_id, 'value' => $value]);
    }

    /**
     * Handle story change.
     */
    protected function handleStoryChange(Instagram $instagram, array $value): void
    {
        Log::info('Instagram story', ['instagram_id' => $instagram->instagram_id, 'value' => $value]);
    }
}

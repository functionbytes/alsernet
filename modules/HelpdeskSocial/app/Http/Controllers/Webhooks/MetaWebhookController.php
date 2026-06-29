<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Modules\HelpdeskSocial\Contracts\WebhookParserInterface;
use Modules\HelpdeskSocial\Contracts\WebhookVerifierInterface;
use Modules\HelpdeskSocial\Jobs\ProcessSocialCommentJob;

class MetaWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookParserInterface $parser,
        private readonly WebhookVerifierInterface $verifier,
    ) {}

    /**
     * Handle webhook verification challenge (GET).
     */
    public function verify(Request $request): Response
    {
        $verifyToken = config('helpdesksocial.integrations.meta.verify_token');
        $challenge = $this->verifier->getChallengeResponse($request, $verifyToken);

        if ($challenge !== false) {
            return response($challenge, 200);
        }

        Log::warning('Meta webhook verification failed', ['ip' => $request->ip()]);

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming Meta webhooks (POST).
     * Supports both Facebook (page) and Instagram (instagram) objects.
     */
    public function handle(Request $request): JsonResponse
    {
        $appSecret = config('helpdesksocial.integrations.meta.app_secret');

        // Fail closed: refuse to process unsigned payloads when no app secret is configured.
        if (! filled($appSecret)) {
            Log::error('Meta webhook rejected: app_secret is not configured', ['ip' => $request->ip()]);

            return response()->json(['status' => 'unauthorized'], 401);
        }

        if (! $this->verifier->verify($request, $appSecret)) {
            Log::warning('Meta webhook signature verification failed');

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $request->all();

        if (! $this->parser->supports($payload)) {
            return response()->json(['status' => 'unsupported'], 400);
        }

        $events = $this->parser->parse($payload);

        foreach ($events as $event) {
            // Skip echo events
            if (($event['type'] ?? '') === 'echo') {
                continue;
            }

            // Process comments and mentions
            if (in_array($event['type'] ?? '', ['comment', 'mention'], true)) {
                ProcessSocialCommentJob::dispatch($event);

                continue;
            }

            // Process DMs/messages - delegate to Helpdesk core if available
            if (in_array($event['type'] ?? '', ['message', 'postback'], true)) {
                $channel = $event['platform'] ?? 'facebook';

                if (class_exists(ProcessSocialWebhookJob::class)) {
                    ProcessSocialWebhookJob::dispatch($channel, $event['type'], $event);
                } else {
                    Log::warning('Helpdesk core module is disabled; skipping DM/message webhook', [
                        'platform' => $channel,
                        'event_type' => $event['type'],
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}

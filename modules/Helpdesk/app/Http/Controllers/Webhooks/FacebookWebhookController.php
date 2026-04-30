<?php

namespace Modules\Helpdesk\Http\Controllers\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Http\Requests\Webhooks\FacebookWebhookRequest;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Modules\Helpdesk\Services\FacebookMessengerService;

class FacebookWebhookController extends Controller
{
    public function __construct(
        private readonly FacebookMessengerService $service
    ) {}

    /**
     * Handle Meta webhook verification challenge (GET).
     */
    public function verify(Request $request): Response
    {
        $challenge = $this->service->verifyWebhook(
            $request->query('hub_mode', ''),
            $request->query('hub_challenge', ''),
            $request->query('hub_verify_token', ''),
        );

        if ($challenge === false) {
            Log::warning('Facebook webhook verify failed — bad token', ['ip' => $request->ip()]);

            return response('Forbidden', 403);
        }

        return response($challenge, 200);
    }

    /**
     * Handle incoming Facebook Messenger events (POST).
     */
    public function handle(FacebookWebhookRequest $request): JsonResponse
    {
        $events = $this->service->parseWebhookPayload($request->all());

        foreach ($events as $event) {
            if (in_array($event['type'], ['message', 'postback'], true)) {
                ProcessSocialWebhookJob::dispatch('facebook', $event['type'], $event);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}

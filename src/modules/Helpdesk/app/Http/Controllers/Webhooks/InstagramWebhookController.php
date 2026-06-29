<?php

namespace Modules\Helpdesk\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Http\Requests\Webhooks\InstagramWebhookRequest;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;

class InstagramWebhookController extends Controller
{
    public function __construct(
        private readonly InstagramService $instagramService,
        private readonly FacebookMessengerService $facebookService,
    ) {}

    /**
     * Handle Meta webhook verification challenge (GET).
     * Instagram uses the same verification flow as Facebook.
     */
    public function verify(Request $request): Response
    {
        $challenge = $this->facebookService->verifyWebhook(
            $request->query('hub_mode', ''),
            $request->query('hub_challenge', ''),
            $request->query('hub_verify_token', ''),
        );

        if ($challenge === false) {
            Log::warning('Instagram webhook verify failed — bad token', ['ip' => $request->ip()]);

            return response('Forbidden', 403);
        }

        return response($challenge, 200);
    }

    /**
     * Handle incoming Instagram DM events (POST).
     */
    public function handle(InstagramWebhookRequest $request): JsonResponse
    {
        $events = $this->instagramService->parseWebhookPayload($request->all());

        foreach ($events as $event) {
            if (in_array($event['type'], ['message', 'story_reply'], true)) {
                ProcessSocialWebhookJob::dispatch('instagram', $event['type'], $event);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}

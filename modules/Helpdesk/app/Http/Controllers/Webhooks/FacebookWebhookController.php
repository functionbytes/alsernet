<?php

namespace Modules\Helpdesk\Http\Controllers\Webhooks;

use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\Webhooks\FacebookWebhookRequest;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Modules\Helpdesk\Services\FacebookMessengerService;

class FacebookWebhookController extends MetaWebhookController
{
    public function __construct(
        private readonly FacebookMessengerService $service
    ) {}

    protected function verifyChallenge(string $mode, string $challenge, string $token): string|false
    {
        return $this->service->verifyWebhook($mode, $challenge, $token);
    }

    protected function channelLabel(): string
    {
        return 'Facebook';
    }

    /**
     * Handle incoming Facebook Messenger events (POST).
     */
    public function handle(FacebookWebhookRequest $request): JsonResponse
    {
        return $this->safelyHandle(function () use ($request): void {
            $events = $this->service->parseWebhookPayload($request->all());

            foreach ($events as $event) {
                if (in_array($event['type'] ?? null, ['message', 'postback', 'reaction'], true)) {
                    ProcessSocialWebhookJob::dispatch('facebook', $event['type'], $event);
                }
            }
        });
    }
}

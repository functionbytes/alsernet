<?php

namespace Modules\Helpdesk\Http\Controllers\Webhooks;

use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\Webhooks\InstagramWebhookRequest;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;

class InstagramWebhookController extends MetaWebhookController
{
    public function __construct(
        private readonly InstagramService $instagramService,
        private readonly FacebookMessengerService $facebookService,
    ) {}

    // Instagram usa el mismo flujo de verificación que Facebook (misma app Meta).
    protected function verifyChallenge(string $mode, string $challenge, string $token): string|false
    {
        return $this->facebookService->verifyWebhook($mode, $challenge, $token);
    }

    protected function channelLabel(): string
    {
        return 'Instagram';
    }

    /**
     * Handle incoming Instagram DM events (POST).
     */
    public function handle(InstagramWebhookRequest $request): JsonResponse
    {
        return $this->safelyHandle(function () use ($request): void {
            $events = $this->instagramService->parseWebhookPayload($request->all());

            foreach ($events as $event) {
                if (in_array($event['type'] ?? null, ['message', 'story_reply'], true)) {
                    ProcessSocialWebhookJob::dispatch('instagram', $event['type'], $event);
                }
            }
        });
    }
}

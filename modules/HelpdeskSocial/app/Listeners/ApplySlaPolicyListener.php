<?php

namespace Modules\HelpdeskSocial\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Services\SlaTrackingService;

class ApplySlaPolicyListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-social-processing';

    public int $tries = 2;

    public int $backoff = 10;

    public function __construct(
        private readonly SlaTrackingService $slaService,
    ) {}

    public function handle(SocialCommentReceived $event): void
    {
        $comment = $event->comment;

        if ($comment->social_sla_policy_id) {
            return;
        }

        try {
            $this->slaService->applyPolicyToComment($comment);
        } catch (\Throwable $e) {
            Log::error('ApplySlaPolicyListener failed', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(SocialCommentReceived $event, \Throwable $exception): void
    {
        Log::error('ApplySlaPolicyListener failed permanently', [
            'comment_id' => $event->comment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

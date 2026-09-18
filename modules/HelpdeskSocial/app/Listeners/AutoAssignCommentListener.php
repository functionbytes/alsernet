<?php

namespace Modules\HelpdeskSocial\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Services\SmartAssignmentService;

class AutoAssignCommentListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-social-processing';

    public int $tries = 2;

    public int $backoff = 10;

    public function __construct(
        private readonly SmartAssignmentService $assignmentService,
    ) {}

    public function handle(SocialCommentReceived $event): void
    {
        $comment = $event->comment;

        if ($comment->assigned_to_user_id) {
            return;
        }

        try {
            $this->assignmentService->assign($comment);
        } catch (\Throwable $e) {
            Log::error('AutoAssignCommentListener failed', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(SocialCommentReceived $event, \Throwable $exception): void
    {
        Log::error('AutoAssignCommentListener failed permanently', [
            'comment_id' => $event->comment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

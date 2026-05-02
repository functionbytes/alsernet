<?php

namespace Modules\HelpdeskSocial\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Services\IntentClassificationService;

class ClassifyIntentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public readonly int $commentId,
    ) {
        $this->onQueue(config('helpdesksocial.queues.processing', 'helpdesk-social-processing'));
    }

    public function handle(IntentClassificationService $classificationService): void
    {
        $comment = SocialComment::find($this->commentId);

        if (! $comment) {
            return;
        }

        try {
            $classificationService->classify($comment);
        } catch (\Throwable $e) {
            Log::error('ClassifyIntentJob failed', [
                'comment_id' => $this->commentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

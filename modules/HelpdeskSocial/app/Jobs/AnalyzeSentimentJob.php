<?php

namespace Modules\HelpdeskSocial\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Services\SentimentAnalysisService;

class AnalyzeSentimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public readonly int $commentId,
    ) {
        $this->onQueue(config('helpdesksocial.queues.processing', 'helpdesk-social-processing'));
    }

    public function handle(SentimentAnalysisService $sentimentService): void
    {
        $comment = SocialComment::find($this->commentId);

        if (! $comment) {
            return;
        }

        try {
            $sentimentService->analyze($comment);
        } catch (\Throwable $e) {
            Log::error('AnalyzeSentimentJob failed', [
                'comment_id' => $this->commentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeSentimentJob failed permanently', [
            'comment_id' => $this->commentId,
            'error' => $exception->getMessage(),
        ]);
    }
}

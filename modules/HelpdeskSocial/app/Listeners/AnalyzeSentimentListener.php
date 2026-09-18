<?php

namespace Modules\HelpdeskSocial\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Jobs\AnalyzeSentimentJob;

class AnalyzeSentimentListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-social-processing';

    public int $tries = 2;

    public int $backoff = 10;

    public function handle(SocialCommentReceived $event): void
    {
        $comment = $event->comment;

        if ($comment->sentiment !== null) {
            return;
        }

        try {
            AnalyzeSentimentJob::dispatch($comment->id);
        } catch (\Throwable $e) {
            Log::error('AnalyzeSentimentListener failed', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(SocialCommentReceived $event, \Throwable $exception): void
    {
        Log::error('AnalyzeSentimentListener failed permanently', [
            'comment_id' => $event->comment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

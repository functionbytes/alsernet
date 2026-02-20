<?php

namespace Modules\Reviews\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Events\ReplyFailed;
use Modules\Reviews\Events\ReplyPublished;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Services\GoogleReviewService;

class PublishReviewReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes

    public int $backoff = 60;

    public function __construct(
        public ReviewReply $reply,
        public User $user
    ) {
        $this->onQueue('google-sync');
    }

    public function handle(GoogleReviewService $service): void
    {
        try {
            $service->publishReply($this->reply);

            $this->reply->markAsPublished($this->user);

            event(new ReplyPublished($this->reply));

            Log::info("Published reply {$this->reply->id} to Google");
        } catch (\Exception $e) {
            $this->reply->markAsFailed($e->getMessage());

            event(new ReplyFailed($this->reply, $e->getMessage()));

            Log::error('Failed to publish reply', [
                'reply_id' => $this->reply->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->reply->markAsFailed($exception->getMessage());

        event(new ReplyFailed($this->reply, $exception->getMessage()));

        Log::error('PublishReviewReplyJob failed permanently', [
            'reply_id' => $this->reply->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

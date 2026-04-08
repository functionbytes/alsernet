<?php

namespace Modules\Reviews\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Events\ReviewSynced;
use Modules\Reviews\Services\AutoReplyService;

class ProcessAutoReplies implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'replies';

    public function __construct(private readonly AutoReplyService $service) {}

    public function handle(ReviewSynced $event): void
    {
        try {
            $this->service->processReview($event->review);
        } catch (\Throwable $e) {
            Log::error('ProcessAutoReplies listener failed', [
                'review_id' => $event->review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

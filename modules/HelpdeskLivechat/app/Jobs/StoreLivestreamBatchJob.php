<?php

namespace Modules\HelpdeskLivechat\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskLivechat\Models\LivestreamEvent;

class StoreLivestreamBatchJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 5;

    public function __construct(
        public readonly int $conversationId,
        public readonly array $events,
        public readonly int $batchIndex,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        LivestreamEvent::create([
            'conversation_id' => $this->conversationId,
            'batch_index' => $this->batchIndex,
            'events' => $this->events,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('StoreLivestreamBatchJob failed', [
            'conversation_id' => $this->conversationId,
            'batch_index' => $this->batchIndex,
            'error' => $exception->getMessage(),
        ]);
    }
}

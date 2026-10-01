<?php

namespace Modules\HelpdeskMedia\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskMedia\Services\ConversationMediaProcessor;

class ProcessConversationMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly int $itemId,
        public readonly bool $force = false,
    ) {
        $this->tries = (int) config('helpdeskmedia.tries', 3);
        $this->timeout = (int) config('helpdeskmedia.timeout', 300);
        $this->onQueue((string) config('helpdeskmedia.queue', 'media-optimize'));
        $this->afterCommit();
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return (array) config('helpdeskmedia.backoff', [30, 120, 300]);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('hdmedia-item-'.$this->itemId))->releaseAfter(20)->expireAfter($this->timeout + 60)];
    }

    public function handle(ConversationMediaProcessor $processor): void
    {
        $item = ConversationItem::query()->find($this->itemId);

        if ($item === null) {
            return;
        }

        $processor->process($item, $this->force);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('HelpdeskMedia: conversation media job failed', [
            'item_id' => $this->itemId,
            'error' => $exception->getMessage(),
        ]);
    }
}

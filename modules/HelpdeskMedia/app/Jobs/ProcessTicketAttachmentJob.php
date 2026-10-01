<?php

namespace Modules\HelpdeskMedia\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskMedia\Services\TicketAttachmentProcessor;
use Modules\HelpdeskTickets\Models\TicketAttachment;

class ProcessTicketAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly int $attachmentId,
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
        return [(new WithoutOverlapping('hdmedia-ticket-att-'.$this->attachmentId))->releaseAfter(20)->expireAfter($this->timeout + 60)];
    }

    public function handle(TicketAttachmentProcessor $processor): void
    {
        if (! TicketAttachmentProcessor::columnsReady()) {
            Log::warning('HelpdeskMedia: helpdesk_ticket_attachments media columns missing, run the migration');

            return;
        }

        $attachment = TicketAttachment::query()->find($this->attachmentId);

        if ($attachment !== null) {
            $processor->process($attachment, $this->force);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('HelpdeskMedia: ticket attachment job failed', [
            'attachment_id' => $this->attachmentId,
            'error' => $exception->getMessage(),
        ]);
    }
}

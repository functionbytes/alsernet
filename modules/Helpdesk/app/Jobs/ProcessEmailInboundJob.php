<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Services\EmailInboundService;

class ProcessEmailInboundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    /**
     * @param  array{from: string, from_name: ?string, subject: string, body: string, message_id: ?string}  $parsed
     */
    public function __construct(
        public readonly array $parsed,
    ) {
        $this->onQueue('helpdesk-webhooks');
    }

    public function middleware(): array
    {
        $key = md5($this->parsed['from'].($this->parsed['message_id'] ?? $this->parsed['subject']));

        return [(new WithoutOverlapping("email-inbound-{$key}"))->dontRelease()];
    }

    public function handle(EmailInboundService $service): void
    {
        $service->process($this->parsed);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessEmailInboundJob failed', [
            'from' => $this->parsed['from'] ?? null,
            'subject' => $this->parsed['subject'] ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}

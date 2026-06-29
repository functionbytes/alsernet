<?php

namespace Modules\Engagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Engagement\Services\VisitorPsContextEnrichmentService;

class EnrichVisitorWithPsContextJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 30;

    public int $backoff = 15;

    public function __construct(
        private readonly string $sessionToken,
        private readonly string $email,
    ) {
        $this->onQueue('engagement');
    }

    public function handle(VisitorPsContextEnrichmentService $service): void
    {
        $service->enrich($this->sessionToken, $this->email);
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('Engagement: EnrichVisitorWithPsContextJob failed', [
            'session_token' => $this->sessionToken,
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }
}

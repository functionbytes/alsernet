<?php

namespace Modules\HelpdeskPrestashop\Jobs;

use App\Helpers\PiiMasker;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

class RefreshPsContextJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 20;

    public int $backoff = 10;

    public function __construct(
        private readonly string $email
    ) {
        $this->onQueue('helpdesk-ps');
    }

    /**
     * Keep deduplication open for the full cache TTL so we never queue a
     * second refresh while a previous one is still in-flight or recent.
     */
    public function uniqueFor(): int
    {
        return (int) config('helpdeskprestashop.cache_ttl', 300);
    }

    public function uniqueId(): string
    {
        return md5(strtolower(trim($this->email)));
    }

    public function handle(PrestashopContextService $service): void
    {
        $service->revalidate($this->email);
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('RefreshPsContextJob failed', [
            'email' => PiiMasker::email($this->email),
            'error' => $e->getMessage(),
        ]);
    }
}

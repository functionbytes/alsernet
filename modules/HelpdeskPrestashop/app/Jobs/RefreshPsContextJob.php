<?php

namespace Modules\HelpdeskPrestashop\Jobs;

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

    public int $uniqueFor = 30;

    public function __construct(
        private readonly string $email
    ) {
        $this->onQueue('helpdesk-ps');
    }

    public function uniqueId(): string
    {
        return md5($this->email);
    }

    public function handle(PrestashopContextService $service): void
    {
        $service->forgetCache($this->email);
        $service->getCustomerContext($this->email);
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('RefreshPsContextJob failed', [
            'email' => $this->email,
            'error' => $e->getMessage(),
        ]);
    }
}

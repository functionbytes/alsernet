<?php

namespace Modules\HelpdeskErp\Jobs;

use App\Helpers\PiiMasker;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskErp\Services\ErpContextService;

class RefreshErpContextJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 90;

    public int $backoff = 10;

    public int $uniqueFor = 30;

    public function __construct(
        private readonly string $email
    ) {
        $this->onQueue('helpdesk-erp');
    }

    public function uniqueId(): string
    {
        return md5($this->email);
    }

    public function handle(ErpContextService $service): void
    {
        $service->forgetCache($this->email);
        $service->getCustomerContext($this->email);
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('RefreshErpContextJob failed', [
            'email' => PiiMasker::email($this->email),
            'error' => $e->getMessage(),
        ]);
    }
}

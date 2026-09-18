<?php

namespace Modules\HelpdeskErp\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskErp\Services\ErpContextService;

class WarmErpCacheJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public int $backoff = 30;

    public function __construct(
        private readonly array $emails
    ) {
        $this->onQueue('helpdesk-erp-warming');
    }

    public function handle(ErpContextService $service): void
    {
        foreach ($this->emails as $email) {
            try {
                $service->getCustomerContext($email);
            } catch (\Throwable) {
                // Mejor esfuerzo — ignorar errores individuales por email
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('WarmErpCacheJob failed', [
            'email_count' => count($this->emails),
            'error' => $e->getMessage(),
        ]);
    }
}

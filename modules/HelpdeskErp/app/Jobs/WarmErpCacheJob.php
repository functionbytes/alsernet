<?php

namespace Modules\HelpdeskErp\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\HelpdeskErp\Services\ErpContextService;

class WarmErpCacheJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

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
                // Mejor esfuerzo — ignorar errores individuales
            }
        }
    }
}

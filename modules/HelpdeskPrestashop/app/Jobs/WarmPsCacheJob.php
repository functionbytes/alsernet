<?php

namespace Modules\HelpdeskPrestashop\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

class WarmPsCacheJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        private readonly array $emails
    ) {
        $this->onQueue('helpdesk-ps-warming');
    }

    public function handle(PrestashopContextService $service): void
    {
        foreach ($this->emails as $email) {
            try {
                $service->getCustomerContext($email);
            } catch (\Throwable) {
                // best effort — continues with next email
            }
        }
    }
}

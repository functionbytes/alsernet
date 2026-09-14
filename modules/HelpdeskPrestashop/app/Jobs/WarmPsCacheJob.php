<?php

namespace Modules\HelpdeskPrestashop\Jobs;

use App\Helpers\PiiMasker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

class WarmPsCacheJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public int $backoff = 30;

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
            } catch (\Throwable $e) {
                Log::debug('WarmPsCacheJob: error warming email', [
                    'email' => PiiMasker::email($email),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('WarmPsCacheJob failed', [
            'email_count' => count($this->emails),
            'error' => $e->getMessage(),
        ]);
    }
}

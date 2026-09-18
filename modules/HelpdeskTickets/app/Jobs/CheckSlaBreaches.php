<?php

namespace Modules\HelpdeskTickets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Services\SlaService;

/**
 * Job to check and mark SLA breaches for tickets
 */
class CheckSlaBreaches implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public int $backoff = 60;

    public function __construct()
    {
        $this->queue = 'helpdesk-scheduled';
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('check-sla-breaches'))->dontRelease()];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CheckSlaBreaches job permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(SlaService $slaService): void
    {
        try {
            Log::info('CheckSlaBreaches job started at '.now());

            // El job recibía SlaService por inyección y no lo usaba: repetía
            // aquí, con diferencias, el mismo barrido que SlaService::
            // checkBreaches(). Ahora delega de verdad, y con ello el filtro de
            // tickets pausados y el logging viven en un solo sitio.
            $breachCount = $slaService->checkBreaches()->count();

            Log::info('CheckSlaBreaches job completed at '.now()." - Total breaches: {$breachCount}");
        } catch (\Exception $e) {
            Log::error("CheckSlaBreaches job failed: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

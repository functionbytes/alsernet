<?php

namespace Modules\HelpdeskTickets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Services\EscalationService;

class EscalateTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct()
    {
        $this->queue = 'helpdesk-scheduled';
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('escalate-tickets'))->dontRelease()];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EscalateTicketsJob permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    public function handle(EscalationService $escalationService): void
    {
        try {
            Log::info('EscalateTicketsJob started at '.now());

            $count = $escalationService->checkAndEscalate();

            Log::info('EscalateTicketsJob completed at '.now()." — {$count} ticket(s) escalated");
        } catch (\Exception $e) {
            Log::error("EscalateTicketsJob failed: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

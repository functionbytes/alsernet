<?php

namespace Modules\HelpdeskTickets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\CatalogCacheService;

class ProcessRecurringTicketsJob implements ShouldQueue
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
        return [(new WithoutOverlapping('process-recurring-tickets'))->dontRelease()];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessRecurringTicketsJob permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    public function handle(): void
    {
        $due = RecurringTicket::dueToRun()->get();

        Log::info("ProcessRecurringTicketsJob: processing {$due->count()} recurring ticket(s).");

        foreach ($due as $recurring) {
            try {
                // status_id explícito: sin él el ticket nace con estado NULL,
                // que no es ningún estado del catálogo — no aparece en ningún
                // tab del listado, la fila sale sin etiqueta y el SLA no
                // arranca. Había tres tickets así generados por este job
                // (TCK-2026-00033, 00034 y 00076).
                Ticket::create([
                    'subject' => $recurring->subject,
                    'description' => $recurring->description,
                    'category_id' => $recurring->category_id,
                    'assignee_id' => $recurring->assignee_id,
                    'status_id' => $this->defaultStatusId(),
                    'source' => 'recurring',
                ]);

                $recurring->update([
                    'last_run_at' => now(),
                    'tickets_created' => $recurring->tickets_created + 1,
                    'next_run_at' => $recurring->calculateNextRun(),
                ]);

                Log::info("ProcessRecurringTicketsJob: created ticket for recurring schedule [{$recurring->id}] \"{$recurring->name}\".");
            } catch (\Throwable $e) {
                Log::error("ProcessRecurringTicketsJob: failed for recurring [{$recurring->id}]: {$e->getMessage()}");
            }
        }
    }

    /**
     * Estado inicial del catálogo: el marcado por defecto y, si no hay
     * ninguno, el primero. Mismo criterio que TicketsCrudController::store().
     */
    private function defaultStatusId(): ?int
    {
        $statuses = CatalogCacheService::statuses();

        return $statuses->firstWhere('is_default', true)?->id ?? $statuses->first()?->id;
    }
}

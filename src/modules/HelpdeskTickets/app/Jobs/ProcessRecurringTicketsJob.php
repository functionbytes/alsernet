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

class ProcessRecurringTicketsJob implements ShouldQueue
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
                Ticket::create([
                    'subject' => $recurring->subject,
                    'description' => $recurring->description,
                    'category_id' => $recurring->category_id,
                    'assignee_id' => $recurring->assignee_id,
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
}

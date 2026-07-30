<?php

namespace Modules\HelpdeskTickets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketDailyReport;

/**
 * Job to generate daily ticket statistics reports
 */
class GenerateTicketReports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public string $queue = 'helpdesk-scheduled';

    /** @var array<int> */
    public array $backoff = [300, 600];

    public function middleware(): array
    {
        return [(new WithoutOverlapping('generate-ticket-reports'))->dontRelease()];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateTicketReports job permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('GenerateTicketReports job started at '.now());

            $yesterday = now()->subDay();
            $startOfDay = $yesterday->copy()->startOfDay();
            $endOfDay = $yesterday->copy()->endOfDay();

            $totalCreated = Ticket::query()
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count();

            $totalClosed = Ticket::query()
                ->whereBetween('closed_at', [$startOfDay, $endOfDay])
                ->count();

            $averageResponseTime = Ticket::query()
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->whereNotNull('first_response_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg')
                ->value('avg');

            $ticketsByCategory = Ticket::query()
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->select('category_id', DB::raw('count(*) as total'))
                ->groupBy('category_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->category_id ?? 'uncategorized' => $item->total];
                })
                ->toArray();

            $ticketsByPriority = Ticket::query()
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->select('priority', DB::raw('count(*) as total'))
                ->groupBy('priority')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->priority => $item->total];
                })
                ->toArray();

            $agentPerformance = Ticket::query()
                ->whereBetween('closed_at', [$startOfDay, $endOfDay])
                ->whereNotNull('assignee_id')
                ->select('assignee_id', DB::raw('count(*) as tickets_closed'))
                ->groupBy('assignee_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->assignee_id => $item->tickets_closed];
                })
                ->toArray();

            // Persist report — update if already generated for this date (idempotent)
            TicketDailyReport::updateOrCreate(
                ['report_date' => $yesterday->toDateString()],
                [
                    'total_created' => $totalCreated,
                    'total_closed' => $totalClosed,
                    'total_resolved' => Ticket::query()
                        ->whereBetween('resolved_at', [$startOfDay, $endOfDay])
                        ->count(),
                    'avg_response_time_minutes' => round($averageResponseTime ?? 0, 2),
                    'avg_resolution_time_minutes' => round(
                        Ticket::query()
                            ->whereBetween('resolved_at', [$startOfDay, $endOfDay])
                            ->whereNotNull('created_at')
                            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg')
                            ->value('avg') ?? 0,
                        2
                    ),
                    'sla_breached_count' => Ticket::query()
                        ->whereBetween('created_at', [$startOfDay, $endOfDay])
                        ->slaBreach()
                        ->count(),
                    'by_category' => $ticketsByCategory,
                    'by_priority' => $ticketsByPriority,
                    'agent_performance' => $agentPerformance,
                ]
            );

            Log::info("Daily ticket report persisted for {$yesterday->toDateString()}", [
                'total_created' => $totalCreated,
                'total_closed' => $totalClosed,
            ]);
        } catch (\Exception $e) {
            Log::error("GenerateTicketReports job failed: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\SlaService;
use Modules\HelpdeskTickets\Services\TicketSettings;

class MarkOverdueTicketsCommand extends Command
{
    protected $signature = 'ticket:autooverdue';

    protected $description = 'Mark tickets as SLA-breached when their due date has passed';

    public function handle(): int
    {
        try {
            $settings = app(TicketSettings::class);

            if (! $settings->boolean('auto_overdue_ticket', false)) {
                $this->info('Automatic SLA breach marking is disabled in Helpdesk settings.');

                return Command::SUCCESS;
            }

            $days = $settings->integer('auto_overdue_ticket_time', 5);
            $resolutionBreached = $this->markResolutionBreaches($days);

            // Primera y siguiente respuesta ya no se marcan aquí: las barre
            // SlaService::checkBreaches() (job CheckSlaBreaches) sin depender
            // de este toggle; mantenerlas en los dos sitios las duplicaba.
            $this->info("Marked {$resolutionBreached} SLA resolution breach(es).");
            Log::info('AutoOverdueTickets: SLA breaches marked.', [
                'resolution' => $resolutionBreached,
                'fallback_days' => $days,
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('AutoOverdueTickets command failed', ['error' => $e->getMessage()]);
            $this->error('Command failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function markResolutionBreaches(int $fallbackDays): int
    {
        $count = 0;
        $sla = app(SlaService::class);

        // Dos consultas disjuntas en lugar de un OR entre sla_resolution_due_at
        // y created_at: el OR impedía usar índice. Ambas arrancan por
        // tickets_sla_check_idx (flag, due_at, closed_at): la primera con un
        // rango sobre due_at y la segunda con due_at IS NULL.
        $queries = [
            $this->openUnbreachedTickets()->where('sla_resolution_due_at', '<', now()),
            // Sin política de SLA no hay plazo que incumplir.
            $this->openUnbreachedTickets()
                ->whereNull('sla_resolution_due_at')
                ->whereNotNull('sla_policy_id')
                ->where('created_at', '<', now()->subDays($fallbackDays)),
        ];

        // Por el mismo camino que CheckSlaBreaches (evento + TicketSlaBreach):
        // un UPDATE masivo dejaba el flag puesto y CheckSlaBreaches, que solo
        // mira tickets sin flag, nunca llegaba a notificar. lazyById y no
        // cursor(): el flag se escribe mientras se recorre.
        foreach ($queries as $query) {
            $query->lazyById(500)->each(function (Ticket $ticket) use ($sla, &$count): void {
                if ($sla->registerResolutionBreach($ticket)) {
                    $count++;
                }
            });
        }

        return $count;
    }

    private function openUnbreachedTickets(): Builder
    {
        return Ticket::query()
            ->whereNull('closed_at')
            ->whereNull('sla_paused_at')
            ->where('sla_resolution_breached', false);
    }
}

<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskTickets\Services\TicketQualityReviewService;

/**
 * Revisa por muestreo la calidad de los tickets cerrados.
 *
 * Pensado para el scheduler, a diario y con una muestra pequeña. El objetivo no
 * es revisarlo todo —eso sería carísimo y nadie leería el resultado— sino tener
 * una medida estable de cómo se está atendiendo, que no dependa de que el
 * cliente conteste al CSAT.
 */
class ReviewTicketQualityCommand extends Command
{
    protected $signature = 'helpdesk:review-quality
                            {--size= : Tickets a revisar (por defecto, el de la config)}
                            {--days=7 : Antigüedad máxima de los tickets cerrados}
                            {--dry-run : Muestra la muestra sin evaluarla}';

    protected $description = 'Revisa por muestreo la calidad de la atención en tickets cerrados';

    public function handle(TicketQualityReviewService $service): int
    {
        if (! $service->isAvailable()) {
            $this->line('Revisión de calidad desactivada o sin agente IA configurado.');

            return self::SUCCESS;
        }

        $size = (int) ($this->option('size') ?: config('helpdesktickets.quality_review.daily_sample', 10));
        $days = max(1, (int) $this->option('days'));

        $tickets = $service->sample($size, $days);

        if ($tickets->isEmpty()) {
            $this->line('No hay tickets cerrados sin revisar en la ventana.');

            return self::SUCCESS;
        }

        $this->line("Muestra: {$tickets->count()} ticket(s).");

        if ($this->option('dry-run')) {
            foreach ($tickets as $ticket) {
                $this->line("  #{$ticket->ticket_number} — {$ticket->subject}");
            }

            return self::SUCCESS;
        }

        $reviewed = 0;
        $scores = [];

        foreach ($tickets as $ticket) {
            $review = $service->review($ticket);

            if ($review === null) {
                $this->line("  #{$ticket->ticket_number}: sin revisión");

                continue;
            }

            $scores[] = $review->score;
            $reviewed++;

            $flag = $review->score <= 2 ? ' ← revisar' : '';
            $this->line("  #{$ticket->ticket_number}: {$review->score}/5{$flag}");
        }

        if ($scores !== []) {
            $this->info(sprintf(
                '%d revisado(s). Media: %.1f/5',
                $reviewed,
                array_sum($scores) / count($scores),
            ));
        }

        return self::SUCCESS;
    }
}

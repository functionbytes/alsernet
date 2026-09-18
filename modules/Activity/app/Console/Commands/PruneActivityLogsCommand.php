<?php

namespace Modules\Activity\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class PruneActivityLogsCommand extends Command
{
    protected $signature = 'activity:prune {--days=90 : Días de antigüedad para eliminar registros}';

    protected $description = 'Eliminar registros de actividad más antiguos que el número de días indicado';

    public function handle(): int
    {
        $days = (int) setting('activity.retention_days', $this->option('days'));

        if ($days < 1) {
            $this->error('El número de días debe ser mayor a 0');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $total = Activity::query()
            ->where('created_at', '<', $cutoff)
            ->count();

        if ($total === 0) {
            $this->info("No se encontraron registros de actividad anteriores a {$cutoff->format('Y-m-d')}");

            return self::SUCCESS;
        }

        $this->info("Eliminando {$total} registros de actividad anteriores a {$cutoff->format('Y-m-d')}...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $deleted = 0;

        Activity::query()
            ->where('created_at', '<', $cutoff)
            ->chunkById(500, function ($records) use ($bar, &$deleted) {
                foreach ($records as $record) {
                    $record->delete();
                    $deleted++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();

        $this->info("Deleted {$deleted} activity log records older than {$days} days");

        return self::SUCCESS;
    }
}

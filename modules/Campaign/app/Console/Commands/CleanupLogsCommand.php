<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Borra logs de tracking antiguos para evitar que las tablas crezcan
 * sin límite. Ejecutado weekly desde el provider.
 *
 *   php artisan campaign:cleanup --older-than=180     # días
 *   php artisan campaign:cleanup --dry-run            # estimación sin borrar
 */
class CleanupLogsCommand extends Command
{
    protected $signature = 'campaign:cleanup
                            {--older-than=180 : Días de antigüedad mínimos para borrar}
                            {--dry-run : Solo cuenta filas a borrar, no las elimina}';

    protected $description = 'Borra open_logs/click_logs/unsubscribe_logs/feedback_logs y tracking_logs antiguos.';

    public function handle(): int
    {
        $days = (int) $this->option('older-than');
        $cutoff = now()->subDays($days);
        $dry = $this->option('dry-run');

        $this->info("Cutoff: {$cutoff} ({$days} días)");
        if ($dry) {
            $this->warn('Modo --dry-run: no se borra nada.');
        }

        // Orden importa: child logs primero por FK constraints.
        $tables = [
            'campaign_open_logs',
            'campaign_click_logs',
            'campaign_unsubscribe_logs',
            'campaign_feedback_logs',
            'campaign_sending_server_bounce_logs',
            'campaign_sending_server_feedback_logs',
            'campaign_tracking_logs',
        ];

        $totalDeleted = 0;
        foreach ($tables as $table) {
            if (! \Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table)->where('created_at', '<', $cutoff);
            $count = $query->count();

            if ($count === 0) {
                $this->line("  · {$table}: 0 (skip)");

                continue;
            }

            if ($dry) {
                $this->line("  · {$table}: {$count} a borrar");
            } else {
                // Batches de 5000 para no bloquear la BD.
                $deleted = 0;
                while (($chunk = DB::table($table)->where('created_at', '<', $cutoff)->limit(5000)->delete()) > 0) {
                    $deleted += $chunk;
                }
                $this->line("  · {$table}: {$deleted} borradas");
                $totalDeleted += $deleted;
            }
        }

        $this->info($dry ? 'Dry run completado.' : "Total filas eliminadas: {$totalDeleted}");

        return self::SUCCESS;
    }
}

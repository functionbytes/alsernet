<?php

namespace Modules\Attention\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Attention\Models\Attention;

/**
 * Command para archivar PQRSF cerrados según Ley 594/2000
 *
 * Marca como archivados los PQRSF cerrados que han cumplido
 * el tiempo de archivo de gestión (por defecto 5 años).
 */
class ArchiveOldAttentionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attention:archive-old
                            {--dry-run : Ejecutar sin hacer cambios}
                            {--years= : Años desde cierre (default: config)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archiva PQRSF cerrados hace X años (Ley 594/2000)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $years = $this->option('years') ?? config('attention.data_retention.management_archive_years', 5);

        $this->info("🗄️  Archivando PQRSF cerrados hace {$years}+ años...");

        if ($dryRun) {
            $this->warn('⚠️  Modo DRY RUN - No se harán cambios');
        }

        // Fecha de corte
        $cutoffDate = Carbon::now()->subYears($years);

        // Buscar PQRSF que cumplan los criterios de archivo
        $attentions = Attention::whereNotNull('closed_at')
            ->where('closed_at', '<=', $cutoffDate)
            ->whereNull('archived_at')
            ->get();

        if ($attentions->isEmpty()) {
            $this->info('✅ No hay PQRSF para archivar');

            return self::SUCCESS;
        }

        $this->info("📋 Encontrados {$attentions->count()} PQRSF para archivar");

        if (! $dryRun) {
            $bar = $this->output->createProgressBar($attentions->count());
            $bar->start();
        }

        $archived = 0;

        foreach ($attentions as $attention) {
            if ($dryRun) {
                $this->line("  - {$attention->radicado} (cerrado: {$attention->closed_at->format('d/m/Y')})");
            } else {
                $attention->update(['archived_at' => now()]);

                // Log de auditoría
                $attention->logAction(
                    'archived',
                    'PQRSF archivado automáticamente según política de retención (Ley 594/2000)',
                    null
                );

                $archived++;
                $bar->advance();
            }
        }

        if (! $dryRun) {
            $bar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info('✅ Archivados: '.($dryRun ? '0 (DRY RUN)' : $archived));

        if ($dryRun && $attentions->count() > 0) {
            $this->info('💡 Ejecute sin --dry-run para aplicar los cambios');
        }

        return self::SUCCESS;
    }
}

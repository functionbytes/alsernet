<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReturnCommunication;
use Carbon\Carbon;

class CleanupOldCommunications extends Command
{
    protected $signature = 'returns:cleanup-communications
                            {--days=90 : Días de antigüedad para eliminar}
                            {--dry-run : Ejecutar sin eliminar registros}';

    protected $description = 'Limpiar comunicaciones antiguas de devoluciones';

    public function handle(): int
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("🧹 Limpiando comunicaciones más antiguas de {$days} días...");

        $query = ReturnCommunication::where('created_at', '<', Carbon::now()->subDays($days))
            ->whereIn('status', ['sent', 'read']);

        $count = $query->count();

        if ($count === 0) {
            $this->info('✅ No hay comunicaciones antiguas para limpiar.');
            return Command::SUCCESS;
        }

        $this->info("📊 Encontradas {$count} comunicaciones para eliminar.");

        if ($dryRun) {
            $this->warn('⚠️  Modo DRY RUN - No se eliminarán registros');
        } else {
            if ($this->confirm("¿Desea eliminar {$count} comunicaciones antiguas?")) {
                $deleted = $query->delete();
                $this->info("✅ {$deleted} comunicaciones eliminadas exitosamente.");
            } else {
                $this->info('❌ Operación cancelada.');
            }
        }

        return Command::SUCCESS;
    }
}



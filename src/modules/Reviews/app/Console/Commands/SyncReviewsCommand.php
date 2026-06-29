<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reviews\Jobs\SyncGoogleReviewsJob;
use Modules\Reviews\Models\ReviewGoogleLocation;

class SyncReviewsCommand extends Command
{
    protected $signature = 'reviews:sync {--connection=} {--location=} {--force}';

    protected $description = 'Sincronizar reseñas de Google manualmente';

    public function handle(): int
    {
        $connectionId = $this->option('connection');
        $locationId = $this->option('location');
        $force = $this->option('force');

        $query = ReviewGoogleLocation::query()->active();

        if ($connectionId) {
            $query->where('connection_id', $connectionId);
            $this->info("Sincronizando ubicaciones de la conexión ID: $connectionId");
        } elseif ($locationId) {
            $query->where('id', $locationId);
            $this->info("Sincronizando ubicación ID: $locationId");
        } else {
            $this->info('Sincronizando todas las ubicaciones activas');
        }

        $locations = $query->get();

        if ($locations->isEmpty()) {
            $this->warn('No se encontraron ubicaciones para sincronizar');

            return self::FAILURE;
        }

        $this->info("Total de ubicaciones a sincronizar: {$locations->count()}");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($locations->count());
        $progressBar->start();

        $synced = 0;
        $failed = 0;

        foreach ($locations as $location) {
            try {
                SyncGoogleReviewsJob::dispatch($location);
                $synced++;
            } catch (\Exception $e) {
                $this->error("Error sincronizando ubicación {$location->id}: {$e->getMessage()}");
                $failed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->newLine();

        $this->info('Resumen de sincronización:');
        $this->line("✅ Ubicaciones procesadas: $synced");
        if ($failed > 0) {
            $this->line("❌ Errores: $failed");
        }

        return self::SUCCESS;
    }
}

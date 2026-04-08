<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reviews\Models\Review;

class PruneOldReviewsCommand extends Command
{
    protected $signature = 'reviews:prune {--days=180}';

    protected $description = 'Eliminar (soft delete) reseñas antiguas';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('El número de días debe ser mayor a 0');

            return self::FAILURE;
        }

        $cutoffDate = now()->subDays($days);

        $this->info("Buscando reseñas anteriores a: {$cutoffDate->format('Y-m-d H:i:s')}");
        $this->newLine();

        $reviewsToDelete = Review::query()
            ->where('review_time', '<', $cutoffDate)
            ->count();

        if ($reviewsToDelete === 0) {
            $this->info('No se encontraron reseñas para eliminar');

            return self::SUCCESS;
        }

        $this->info("Se encontraron $reviewsToDelete reseñas para eliminar");

        if (! $this->confirm('¿Deseas continuar con la eliminación?')) {
            $this->info('Operación cancelada');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Eliminando reseñas...');

        $progressBar = $this->output->createProgressBar($reviewsToDelete);
        $progressBar->start();

        Review::query()
            ->where('review_time', '<', $cutoffDate)
            ->chunk(100, function ($reviews) use ($progressBar) {
                foreach ($reviews as $review) {
                    $review->delete();
                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine();
        $this->newLine();

        $this->info("✅ Se eliminaron $reviewsToDelete reseñas correctamente");

        return self::SUCCESS;
    }
}

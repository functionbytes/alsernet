<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\ReviewScreener;

/**
 * Criba las opiniones pendientes que aún no ha mirado nadie.
 */
class ScreenReviewsCommand extends Command
{
    protected $signature = 'reviews:screen
                            {--limit=50 : Cuántas cribar como mucho}
                            {--all : Incluir también las ya cribadas}';

    protected $description = 'Marca con IA las opiniones pendientes que parecen problemáticas';

    public function handle(ReviewScreener $screener): int
    {
        if (! ReviewScreener::isEnabled()) {
            $this->warn('La revisión asistida está desactivada en los ajustes.');

            return self::SUCCESS;
        }

        $opiniones = Review::pending()
            ->when(! $this->option('all'), fn ($q) => $q->whereNull('screened_at'))
            ->whereNotNull('comment')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($opiniones->isEmpty()) {
            $this->info('No hay nada que cribar.');

            return self::SUCCESS;
        }

        $barra = $this->output->createProgressBar($opiniones->count());
        $barra->start();

        $marcadas = 0;
        $fallos = 0;

        foreach ($opiniones as $review) {
            $r = $screener->screen($review);

            if (! $r['ok']) {
                $fallos++;
            } elseif ($r['verdict'] !== ReviewScreener::CLEAN) {
                $marcadas++;
            }

            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);
        $this->info($opiniones->count().' cribadas · '.$marcadas.' marcadas'.($fallos ? ' · '.$fallos.' fallidas' : ''));

        return self::SUCCESS;
    }
}

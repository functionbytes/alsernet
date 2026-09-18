<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reviews\Models\ReviewSource;
use Modules\Reviews\Services\GoogleBusinessClient;
use Modules\Reviews\Services\GoogleReviewImporter;

/**
 * Lectura diaria de las fichas de Google.
 *
 * Sustituye a la importación manual que dejó las 1.747 reseñas que ya había:
 * en vez de una carga puntual cada mucho tiempo, lo nuevo llega cada día a la
 * bandeja y se publica cuando alguien lo aprueba.
 */
class FetchGoogleReviewsCommand extends Command
{
    protected $signature = 'reviews:fetch-google
                            {--source= : Leer solo esta ficha (id)}
                            {--test : Solo comprobar la conexión, sin traer nada}';

    protected $description = 'Lee las reseñas nuevas de las fichas de Google configuradas';

    public function handle(GoogleReviewImporter $importer): int
    {
        $fichas = ReviewSource::query()
            ->where('platform', ReviewSource::PLATFORM_GOOGLE)
            ->when($this->option('source'), fn ($q) => $q->where('id', (int) $this->option('source')))
            ->when(! $this->option('source'), fn ($q) => $q->active())
            ->get();

        if ($fichas->isEmpty()) {
            $this->warn('No hay fichas de Google configuradas y activas.');

            return self::SUCCESS;
        }

        if ($this->option('test')) {
            $cliente = app(GoogleBusinessClient::class);

            foreach ($fichas as $ficha) {
                $r = $cliente->testConnection($ficha);
                $this->line(($r['ok'] ? '  OK    ' : '  FALLO ').$ficha->name.': '.$r['message']);
            }

            return self::SUCCESS;
        }

        $totalNuevas = 0;
        $fallos = 0;

        foreach ($fichas as $ficha) {
            $r = $importer->import($ficha);

            if ($r['ok']) {
                $this->info(sprintf('%s: %d nuevas, %d actualizadas', $ficha->name, $r['nuevas'], $r['actualizadas']));
                $totalNuevas += $r['nuevas'];
            } else {
                $this->error($ficha->name.': '.$r['error']);
                $fallos++;
            }
        }

        $this->newLine();
        $this->info($totalNuevas.' reseñas nuevas esperando revisión.');

        return $fallos ? self::FAILURE : self::SUCCESS;
    }
}

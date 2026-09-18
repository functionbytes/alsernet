<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Macro: corre `media:convert-webp` + `media:generate-srcset` en secuencia.
 * Un solo comando desde el panel = todas las optimizaciones de imágenes
 * del Media en un pase.
 */
class OptimizeAllCommand extends Command
{
    protected $signature = 'media:optimize-all
                            {--quality=82 : Calidad WebP (0-100)}
                            {--limit=0 : Procesar solo N archivos (0 = todos)}
                            {--force : Regenerar aunque ya existan}';

    protected $description = 'Ejecuta convert-webp + generate-srcset en una sola pasada';

    public function handle(): int
    {
        $q = (int) $this->option('quality');
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');

        $opts = ['--quality' => $q, '--limit' => $limit];
        if ($force) {
            $opts['--force'] = true;
        }

        $this->info('▸ Paso 1/2 — media:convert-webp');
        $this->newLine();
        Artisan::call('media:convert-webp', $opts, $this->getOutput());

        $this->newLine();
        $this->info('▸ Paso 2/2 — media:generate-srcset');
        $this->newLine();
        Artisan::call('media:generate-srcset', $opts, $this->getOutput());

        $this->newLine();
        $this->info('✔ Optimización de imágenes completa.');

        return self::SUCCESS;
    }
}

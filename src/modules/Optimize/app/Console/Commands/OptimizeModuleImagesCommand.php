<?php

declare(strict_types=1);

namespace Modules\Optimize\Console\Commands;

use Illuminate\Console\Command;
use Modules\Optimize\Services\AssetOptimizerService;

class OptimizeModuleImagesCommand extends Command
{
    protected $signature = 'optimize:optimize-module-images
                            {--quality=82 : Calidad WebP (0-100)}
                            {--limit=0 : Máximo de imágenes a procesar (0 = todas)}';

    protected $description = 'Convertir imágenes de todos los módulos activos a WebP';

    public function handle(AssetOptimizerService $optimizer): int
    {
        $quality = max(0, min(100, (int) $this->option('quality')));
        $limit = max(0, (int) $this->option('limit'));

        $this->info('Escaneando imágenes de módulos activos…');
        $this->newLine();

        $result = $optimizer->optimizeAllModuleImages($quality, $limit);

        if ($result['converted'] > 0) {
            $this->info("✔ Imágenes convertidas: {$result['converted']}");
            $this->info("  Ahorro: {$result['saved_formatted']}");
        } else {
            $this->warn('No se convirtieron imágenes (todas ya tienen WebP o no hay imágenes).');
        }

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->error('Errores:');
            foreach ($result['errors'] as $err) {
                $this->line('  - '.$err);
            }
        }

        return self::SUCCESS;
    }
}

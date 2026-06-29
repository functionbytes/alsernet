<?php

declare(strict_types=1);

namespace Modules\Optimize\Console\Commands;

use Illuminate\Console\Command;
use Modules\Optimize\Services\ThemeOptimizerService;

/**
 * Convierte imágenes del tema activo a WebP.
 * Lee el tema desde setting('template') y procesa platform/themes/{theme}/public/images.
 */
class OptimizeThemeImagesCommand extends Command
{
    protected $signature = 'optimize:theme-images
                            {--theme= : Tema objetivo (por defecto el activo)}
                            {--quality=82 : Calidad WebP (0-100)}
                            {--limit=0 : Máximo de imágenes a procesar (0 = todas)}';

    protected $description = 'Convertir imágenes del tema a WebP';

    public function handle(ThemeOptimizerService $optimizer): int
    {
        $theme = $this->option('theme') ?: $optimizer->getActiveTheme();
        $quality = max(0, min(100, (int) $this->option('quality')));
        $limit = max(0, (int) $this->option('limit'));

        $this->info("Tema: {$theme}");
        $this->info('Calidad WebP: '.$quality);
        if ($limit > 0) {
            $this->info('Límite: '.$limit.' imágenes');
        }
        $this->newLine();

        $result = $optimizer->optimizeThemeImages($theme, $quality, $limit);

        if ($result['converted'] > 0) {
            $this->info("✔ Imágenes convertidas: {$result['converted']}");
            $this->info("  Ahorro: {$result['saved_formatted']}");
        } else {
            $this->warn('No se convirtieron imágenes (todas ya tienen WebP o no hay imágenes).');
        }

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->error('Errores:');
            foreach ($result['errors'] as $error) {
                $this->line('  - '.$error);
            }
        }

        return self::SUCCESS;
    }
}

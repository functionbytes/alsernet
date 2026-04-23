<?php

namespace Modules\Optimize\Console\Commands;

use Illuminate\Console\Command;
use Modules\Optimize\Services\ThemeOptimizerService;

/**
 * Genera archivos `.min.css` y `.min.js` de los assets declarados en
 * config.php del tema activo. Solo toca los archivos listados en
 * `assets.css` y `assets.js`, no todo el directorio public/.
 *
 * El middleware `RewriteMinAssets` (si está activo) reescribe las URLs
 * hacia la versión .min en runtime.
 */
class MinifyThemeAssetsCommand extends Command
{
    protected $signature = 'optimize:minify-theme-assets
                            {--theme= : Tema objetivo (por defecto el activo)}
                            {--force : Regenerar aunque el .min sea más reciente que el origen}';

    protected $description = 'Generar .min.css y .min.js de los assets declarados del tema';

    public function handle(ThemeOptimizerService $optimizer): int
    {
        $theme = $this->option('theme') ?: $optimizer->getActiveTheme();
        $force = (bool) $this->option('force');

        $this->info("Tema: {$theme}");
        $this->newLine();

        $assets = $optimizer->getDeclaredAssets($theme);

        if (empty($assets)) {
            $this->warn('No hay assets declarados en config.php para minificar.');

            return self::SUCCESS;
        }

        $this->info('Assets declarados: '.count($assets));
        foreach (array_keys($assets) as $name) {
            $this->line("  - {$name}");
        }
        $this->newLine();

        $result = $optimizer->minifyDeclaredAssets($theme, $force);

        $css = $result['css'];
        $js = $result['js'];

        if ($css['count'] > 0) {
            $this->info("✔ CSS minificados: {$css['count']}  (".$this->fmt($css['before']).' → '.$this->fmt($css['after']).', '.$this->pct($css['before'], $css['after']).' menos)');
        } else {
            $this->warn('CSS: todos los .min están actualizados.');
        }

        if ($js['count'] > 0) {
            $this->info("✔ JS  minificados: {$js['count']}  (".$this->fmt($js['before']).' → '.$this->fmt($js['after']).', '.$this->pct($js['before'], $js['after']).' menos)');
        } else {
            $this->warn('JS: todos los .min están actualizados.');
        }

        $this->newLine();
        $this->line('Activa `optimize.rewrite_min_assets` en settings para que el middleware sirva las versiones .min.');

        return self::SUCCESS;
    }

    private function fmt(int $bytes): string
    {
        return $bytes >= 1024 ? number_format($bytes / 1024, 1).' KB' : $bytes.' B';
    }

    private function pct(int $before, int $after): string
    {
        if ($before <= 0) {
            return '0 %';
        }

        return number_format((1 - $after / $before) * 100, 1).' %';
    }
}

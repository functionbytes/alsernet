<?php

namespace Modules\Optimize\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Genera archivos `.min.css` y `.min.js` junto a los CSS/JS originales
 * de un theme. El middleware `RewriteAssetsToMinified` (si está activo)
 * reescribe las URLs de assets hacia la versión minificada en runtime.
 *
 * Ataca "Minifica los archivos CSS" (136 KB) y "Minifica los recursos
 * JavaScript" (26 KB) en PageSpeed.
 *
 * Minificación simple (string-based), suficiente para CSS/JS de terceros
 * ya mayormente bien-formados. No es un parser — no intenta tree-shake.
 */
class MinifyThemeAssetsCommand extends Command
{
    protected $signature = 'optimize:minify-theme-assets
                            {slug : carpeta del theme en platform/themes}
                            {--force : Regenerar aunque el .min sea más reciente que el origen}';

    protected $description = 'Generar .min.css y .min.js de los assets de un theme';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $root = base_path("platform/themes/{$slug}/public");
        if (! is_dir($root)) {
            $this->error("Theme no encontrado: {$root}");

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        $cssBefore = $cssAfter = 0;
        $jsBefore = $jsAfter = 0;
        $cssCount = $jsCount = 0;

        $finder = (new Finder)->files()->in($root)->name(['*.css', '*.js'])
            ->notName('*.min.css')->notName('*.min.js');

        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $ext = strtolower($file->getExtension());
            $minPath = preg_replace('/\.'.$ext.'$/', '.min.'.$ext, $path);

            if (! $force && is_file($minPath) && filemtime($minPath) >= filemtime($path)) {
                continue;
            }

            $original = file_get_contents($path);
            $minified = $ext === 'css'
                ? self::minifyCss($original)
                : self::minifyJs($original);

            file_put_contents($minPath, $minified);

            if ($ext === 'css') {
                $cssCount++;
                $cssBefore += strlen($original);
                $cssAfter += strlen($minified);
            } else {
                $jsCount++;
                $jsBefore += strlen($original);
                $jsAfter += strlen($minified);
            }
        }

        $this->info("✔ CSS minificados: {$cssCount}  (".self::fmt($cssBefore).' → '.self::fmt($cssAfter).', '.self::pct($cssBefore, $cssAfter).' menos)');
        $this->info("✔ JS minificados:  {$jsCount}  (".self::fmt($jsBefore).' → '.self::fmt($jsAfter).', '.self::pct($jsBefore, $jsAfter).' menos)');
        $this->newLine();
        $this->line('Activa `optimize.rewrite_min_assets` en settings para que el middleware sirva las versiones .min.');

        return self::SUCCESS;
    }

    private static function minifyCss(string $css): string
    {
        // Remove comments /* ... */
        $css = preg_replace('!/\*[\s\S]*?\*/!', '', $css) ?? $css;
        // Collapse whitespace around symbols
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css) ?? $css;
        // Collapse consecutive whitespace
        $css = preg_replace('/\s+/', ' ', $css) ?? $css;
        // Remove last ; before }
        $css = preg_replace('/;}/', '}', $css) ?? $css;

        return trim($css);
    }

    private static function minifyJs(string $js): string
    {
        // VERY conservative — just strip // comments at the start of lines and
        // /* ... */ blocks, plus collapse blank lines. A real JS minifier
        // (terser) would be safer, but most theme JS is already minified.
        $js = preg_replace('!^\s*//[^\n]*$!m', '', $js) ?? $js;
        $js = preg_replace('!/\*[\s\S]*?\*/!', '', $js) ?? $js;
        $js = preg_replace('/\n\s*\n/', "\n", $js) ?? $js;

        return trim($js);
    }

    private static function fmt(int $bytes): string
    {
        return $bytes >= 1024 ? number_format($bytes / 1024, 1).' KB' : $bytes.' B';
    }

    private static function pct(int $before, int $after): string
    {
        if ($before <= 0) {
            return '0 %';
        }

        return number_format((1 - $after / $before) * 100, 1).' %';
    }
}

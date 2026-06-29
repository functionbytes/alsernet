<?php

declare(strict_types=1);

namespace Modules\Optimize\Services;

/**
 * Genera un Critical CSS inline a partir de los assets declarados en
 * config.php del tema activo.
 *
 * Heurística: toma los primeros 20 KB de cada CSS declarado (donde
 * normalmente viven resets, tipografía, grid system y utilidades base)
 * y los concatena en un archivo critical.css.
 *
 * El middleware InjectCriticalCss inyecta este contenido inline en
 * <head> y carga el resto de CSS de forma no bloqueante.
 */
class CriticalCssService
{
    private ThemeOptimizerService $themeOptimizer;

    public function __construct(ThemeOptimizerService $themeOptimizer)
    {
        $this->themeOptimizer = $themeOptimizer;
    }

    /**
     * Genera el archivo critical.css para el tema indicado.
     *
     * @return array<string, mixed>
     */
    public function generate(?string $theme = null, int $bytesPerFile = 20480): array
    {
        $config = $this->themeOptimizer->getThemeConfig($theme);
        $cssFiles = $config['assets']['css'] ?? [];
        $themePath = $this->themeOptimizer->getThemePath($theme);
        $criticalPath = $themePath.'/public/css/critical.css';

        $extracted = [];
        $totalBytes = 0;

        foreach ($cssFiles as $name => $relativePath) {
            $fullPath = $themePath.'/'.$relativePath;
            if (! is_file($fullPath) || str_ends_with($relativePath, '.min.css')) {
                continue;
            }

            $content = file_get_contents($fullPath);
            // Extraer los primeros N bytes (cortar en el cierre de una regla)
            $chunk = substr($content, 0, $bytesPerFile);
            $lastBrace = strrpos($chunk, '}');
            if ($lastBrace !== false) {
                $chunk = substr($chunk, 0, $lastBrace + 1);
            }

            $extracted[] = "/* {$name} */\n".$chunk;
            $totalBytes += strlen($chunk);
        }

        if (empty($extracted)) {
            return ['success' => false, 'message' => 'No se encontraron CSS para extraer.'];
        }

        $output = "/* Critical CSS generado automáticamente */\n\n".implode("\n\n", $extracted);
        file_put_contents($criticalPath, $output);

        return [
            'success' => true,
            'path' => $criticalPath,
            'files' => count($extracted),
            'size' => strlen($output),
            'size_formatted' => $this->formatBytes(strlen($output)),
        ];
    }

    /**
     * Obtiene el contenido del critical.css generado.
     */
    public function getInlineContent(?string $theme = null): ?string
    {
        $themePath = $this->themeOptimizer->getThemePath($theme);
        $criticalPath = $themePath.'/public/css/critical.css';

        if (! is_file($criticalPath)) {
            return null;
        }

        return file_get_contents($criticalPath);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}

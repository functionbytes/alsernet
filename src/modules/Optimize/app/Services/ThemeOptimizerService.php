<?php

declare(strict_types=1);

namespace Modules\Optimize\Services;

use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Symfony\Component\Finder\Finder;

/**
 * Servicio de optimización de recursos del tema activo.
 *
 * Lee la configuración del tema desde platform/themes/{theme}/config.php
 * y optimiza únicamente los assets declarados (no todos los archivos del
 * directorio). También optimiza imágenes del tema a WebP.
 */
class ThemeOptimizerService
{
    private ?string $themeName = null;

    private ?array $themeConfig = null;

    public function getActiveTheme(): string
    {
        if ($this->themeName === null) {
            $this->themeName = setting('template', 'default');
        }

        return $this->themeName;
    }

    public function getThemePath(?string $theme = null): string
    {
        $theme ??= $this->getActiveTheme();

        return base_path("platform/themes/{$theme}");
    }

    public function getThemeConfig(?string $theme = null): array
    {
        if ($this->themeConfig !== null && $theme === null) {
            return $this->themeConfig;
        }

        $path = $this->getThemePath($theme).'/config.php';

        if (! is_file($path)) {
            return [];
        }

        try {
            $config = require $path;

            if ($theme === null) {
                $this->themeConfig = $config;
            }

            return is_array($config) ? $config : [];
        } catch (\Throwable $e) {
            Log::warning("Theme config.php error for [{$theme}]: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    public function getDeclaredAssets(?string $theme = null): array
    {
        $config = $this->getThemeConfig($theme);
        $assets = $config['assets'] ?? [];
        $css = $assets['css'] ?? [];
        $js = $assets['js'] ?? [];

        $themePath = $this->getThemePath($theme);
        $result = [];

        foreach (['css' => $css, 'js' => $js] as $type => $files) {
            foreach ($files as $name => $relativePath) {
                $fullPath = $themePath.'/'.$relativePath;
                if (is_file($fullPath) && ! str_contains($relativePath, '.min.')) {
                    $result[$name] = $fullPath;
                }
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAssetsStats(?string $theme = null): array
    {
        $assets = $this->getDeclaredAssets($theme);
        $totalSize = 0;
        $count = 0;

        foreach ($assets as $path) {
            if (is_file($path)) {
                $totalSize += filesize($path);
                $count++;
            }
        }

        return [
            'count' => $count,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
        ];
    }

    /**
     * Minifica solo los assets declarados en config.php.
     *
     * @return array<string, mixed>
     */
    public function minifyDeclaredAssets(?string $theme = null, bool $force = false): array
    {
        $assets = $this->getDeclaredAssets($theme);
        $cssCount = $jsCount = 0;
        $cssBefore = $cssAfter = $jsBefore = $jsAfter = 0;

        foreach ($assets as $path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $minPath = preg_replace('/\.'.$ext.'$/', '.min.'.$ext, $path);

            if (! $force && is_file($minPath) && filemtime($minPath) >= filemtime($path)) {
                continue;
            }

            $original = file_get_contents($path);
            $minified = $ext === 'css'
                ? $this->minifyCss($original)
                : $this->minifyJs($original);

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

        return [
            'css' => ['count' => $cssCount, 'before' => $cssBefore, 'after' => $cssAfter],
            'js' => ['count' => $jsCount, 'before' => $jsBefore, 'after' => $jsAfter],
        ];
    }

    /**
     * Optimiza imágenes del tema a WebP.
     *
     * @return array<string, mixed>
     */
    public function optimizeThemeImages(?string $theme = null, int $quality = 82, int $limit = 0): array
    {
        $themePath = $this->getThemePath($theme);
        $imagesDir = $themePath.'/public/images';

        if (! is_dir($imagesDir)) {
            return ['converted' => 0, 'saved' => 0, 'errors' => []];
        }

        $finder = (new Finder)->files()->in($imagesDir)
            ->name(['*.jpg', '*.jpeg', '*.png'])
            ->notName('*.webp');

        $converted = 0;
        $saved = 0;
        $errors = [];
        $processed = 0;
        $manager = ImageManager::gd();

        foreach ($finder as $file) {
            if ($limit > 0 && $processed >= $limit) {
                break;
            }

            $path = $file->getRealPath();
            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $path);

            if (is_file($webpPath) && filemtime($webpPath) >= filemtime($path)) {
                continue;
            }

            try {
                $originalSize = filesize($path);
                $image = $manager->read($path);
                $image->toWebp($quality)->save($webpPath);
                $webpSize = filesize($webpPath);

                $converted++;
                $saved += max(0, $originalSize - $webpSize);
            } catch (\Throwable $e) {
                $errors[] = basename($path).': '.$e->getMessage();
            }

            $processed++;
        }

        return [
            'converted' => $converted,
            'saved' => $saved,
            'saved_formatted' => $this->formatBytes($saved),
            'errors' => $errors,
        ];
    }

    /**
     * Devuelve la lista de imágenes del tema que aún no tienen WebP.
     */
    public function getPendingImagesCount(?string $theme = null): int
    {
        $themePath = $this->getThemePath($theme);
        $imagesDir = $themePath.'/public/images';

        if (! is_dir($imagesDir)) {
            return 0;
        }

        $count = 0;
        $finder = (new Finder)->files()->in($imagesDir)
            ->name(['*.jpg', '*.jpeg', '*.png']);

        foreach ($finder as $file) {
            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file->getRealPath());
            if (! is_file($webpPath)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Obtiene los CSS críticos del tema para precarga.
     *
     * @return array<int, string>
     */
    public function getCriticalCss(?string $theme = null): array
    {
        $config = $this->getThemeConfig($theme);
        $css = $config['assets']['css'] ?? [];
        $critical = [];

        // Priorizar los CSS más importantes
        $priorityKeys = ['styles', 'bootstrap'];
        foreach ($priorityKeys as $key) {
            if (isset($css[$key])) {
                $critical[] = asset($css[$key]);
            }
        }

        return array_values(array_unique($critical));
    }

    /**
     * Verifica si el tema tiene webpack.mix.js y si assets existe.
     */
    public function canCompileAssets(?string $theme = null): bool
    {
        $themePath = $this->getThemePath($theme);

        return is_file($themePath.'/webpack.mix.js')
            && is_dir($themePath.'/assets');
    }

    private function minifyCss(string $css): string
    {
        $css = preg_replace('!/\*[\s\S]*?\*/!', '', $css) ?? $css;
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css) ?? $css;
        $css = preg_replace('/\s+/', ' ', $css) ?? $css;
        $css = preg_replace('/;}/', '}', $css) ?? $css;

        return trim($css);
    }

    private function minifyJs(string $js): string
    {
        $js = preg_replace('!^\s*//[^\n]*$!m', '', $js) ?? $js;
        $js = preg_replace('!/\*[\s\S]*?\*/!', '', $js) ?? $js;
        $js = preg_replace('/\n\s*\n/', "\n", $js) ?? $js;

        return trim($js);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}

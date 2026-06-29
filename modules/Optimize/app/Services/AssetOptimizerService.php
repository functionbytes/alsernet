<?php

declare(strict_types=1);

namespace Modules\Optimize\Services;

use Intervention\Image\ImageManager;

/**
 * Orquesta la optimización de assets de todos los módulos activos
 * y del tema activo.
 */
class AssetOptimizerService
{
    private ThemeOptimizerService $themeOptimizer;

    private ModuleAssetScanner $moduleScanner;

    public function __construct(
        ThemeOptimizerService $themeOptimizer,
        ModuleAssetScanner $moduleScanner
    ) {
        $this->themeOptimizer = $themeOptimizer;
        $this->moduleScanner = $moduleScanner;
    }

    /**
     * Minifica CSS/JS de todos los módulos activos.
     *
     * @return array<string, mixed>
     */
    public function minifyAllModuleAssets(bool $force = false): array
    {
        $modules = $this->moduleScanner->scanAll();
        $cssCount = $jsCount = 0;
        $cssBefore = $cssAfter = $jsBefore = $jsAfter = 0;
        $errors = [];

        foreach ($modules as $name => $data) {
            foreach ($data['css'] as $file) {
                try {
                    $result = $this->minifyFile($file['path'], 'css', $force);
                    if ($result['minified']) {
                        $cssCount++;
                        $cssBefore += $result['before'];
                        $cssAfter += $result['after'];
                    }
                } catch (\Throwable $e) {
                    $errors[] = $file['relative'].': '.$e->getMessage();
                }
            }

            foreach ($data['js'] as $file) {
                try {
                    $result = $this->minifyFile($file['path'], 'js', $force);
                    if ($result['minified']) {
                        $jsCount++;
                        $jsBefore += $result['before'];
                        $jsAfter += $result['after'];
                    }
                } catch (\Throwable $e) {
                    $errors[] = $file['relative'].': '.$e->getMessage();
                }
            }
        }

        return [
            'css' => ['count' => $cssCount, 'before' => $cssBefore, 'after' => $cssAfter],
            'js' => ['count' => $jsCount, 'before' => $jsBefore, 'after' => $jsAfter],
            'errors' => $errors,
        ];
    }

    /**
     * Optimiza imágenes de todos los módulos activos a WebP.
     *
     * @return array<string, mixed>
     */
    public function optimizeAllModuleImages(int $quality = 82, int $limit = 0): array
    {
        $modules = $this->moduleScanner->scanAll();
        $converted = 0;
        $saved = 0;
        $errors = [];
        $processed = 0;

        $manager = ImageManager::gd();

        foreach ($modules as $name => $data) {
            foreach ($data['images'] as $img) {
                if ($limit > 0 && $processed >= $limit) {
                    break 2;
                }

                $path = $img['path'];
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
                    $errors[] = $img['relative'].': '.$e->getMessage();
                }

                $processed++;
            }
        }

        return [
            'converted' => $converted,
            'saved' => $saved,
            'saved_formatted' => $this->formatBytes($saved),
            'errors' => $errors,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getGlobalStats(?array $modules = null): array
    {
        $modules ??= $this->moduleScanner->scanAll();
        $moduleCount = count($modules);
        $totalCss = $totalJs = $totalImages = 0;
        $totalSize = 0;

        foreach ($modules as $data) {
            $totalCss += $data['css_count'];
            $totalJs += $data['js_count'];
            $totalImages += $data['image_count'];
            $totalSize += array_sum(array_column($data['css'], 'size'));
            $totalSize += array_sum(array_column($data['js'], 'size'));
            $totalSize += array_sum(array_column($data['images'], 'size'));
        }

        $themeStats = $this->themeOptimizer->getAssetsStats();

        return [
            'modules' => $moduleCount,
            'module_css' => $totalCss,
            'module_js' => $totalJs,
            'module_images' => $totalImages,
            'module_size' => $this->formatBytes($totalSize),
            'theme_css' => $themeStats['count'],
            'theme_size' => $themeStats['total_size_formatted'],
            'total_size' => $this->formatBytes($totalSize + $themeStats['total_size']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function minifyFile(string $path, string $type, bool $force): array
    {
        $minPath = preg_replace('/\.'.$type.'$/', '.min.'.$type, $path);

        if (! $force && is_file($minPath) && filemtime($minPath) >= filemtime($path)) {
            return ['minified' => false];
        }

        $original = file_get_contents($path);
        $minified = $type === 'css'
            ? $this->minifyCss($original)
            : $this->minifyJs($original);

        file_put_contents($minPath, $minified);

        return [
            'minified' => true,
            'before' => strlen($original),
            'after' => strlen($minified),
        ];
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

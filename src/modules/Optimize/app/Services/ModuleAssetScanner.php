<?php

declare(strict_types=1);

namespace Modules\Optimize\Services;

use Nwidart\Modules\Facades\Module;
use Symfony\Component\Finder\Finder;

/**
 * Escanea todos los módulos activos y detecta sus assets:
 * CSS, JS e imágenes en public/ y resources/assets/.
 */
class ModuleAssetScanner
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function scanAll(): array
    {
        $modules = [];

        foreach (Module::allEnabled() as $module) {
            $name = $module->getName();
            $lower = strtolower($name);
            $path = $module->getPath();

            $css = $this->findCss($path, $lower);
            $js = $this->findJs($path, $lower);
            $images = $this->findImages($path, $lower);

            if (empty($css) && empty($js) && empty($images)) {
                continue;
            }

            $modules[$name] = [
                'name' => $name,
                'lower' => $lower,
                'path' => $path,
                'css' => $css,
                'js' => $js,
                'images' => $images,
                'css_count' => count($css),
                'js_count' => count($js),
                'image_count' => count($images),
                'total_size' => $this->calcTotalSize(array_merge($css, $js, $images)),
            ];
        }

        return $modules;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCss(string $modulePath, string $lowerName): array
    {
        $results = [];

        // Buscar en modules/{Module}/public/css/
        $dir = $modulePath.'/public/css';
        if (is_dir($dir)) {
            foreach (glob($dir.'/*.css') ?: [] as $file) {
                if (str_ends_with($file, '.min.css')) {
                    continue;
                }
                $results[] = $this->fileInfo($file);
            }
        }

        // Buscar en public/modules/{name}/css/
        $pubDir = public_path("modules/{$lowerName}/css");
        if (is_dir($pubDir)) {
            foreach (glob($pubDir.'/*.css') ?: [] as $file) {
                if (str_ends_with($file, '.min.css')) {
                    continue;
                }
                $results[] = $this->fileInfo($file);
            }
        }

        // Buscar en modules/{Module}/resources/assets/sass/
        $sassDir = $modulePath.'/resources/assets/sass';
        if (is_dir($sassDir)) {
            foreach (glob($sassDir.'/*.scss') ?: [] as $file) {
                $results[] = $this->fileInfo($file);
            }
        }

        return $results;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findJs(string $modulePath, string $lowerName): array
    {
        $results = [];

        $dir = $modulePath.'/public/js';
        if (is_dir($dir)) {
            foreach (glob($dir.'/*.js') ?: [] as $file) {
                if (str_ends_with($file, '.min.js')) {
                    continue;
                }
                $results[] = $this->fileInfo($file);
            }
        }

        $pubDir = public_path("modules/{$lowerName}/js");
        if (is_dir($pubDir)) {
            foreach (glob($pubDir.'/*.js') ?: [] as $file) {
                if (str_ends_with($file, '.min.js')) {
                    continue;
                }
                $results[] = $this->fileInfo($file);
            }
        }

        $assetsDir = $modulePath.'/resources/assets/js';
        if (is_dir($assetsDir)) {
            foreach (glob($assetsDir.'/*.js') ?: [] as $file) {
                $results[] = $this->fileInfo($file);
            }
        }

        return $results;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findImages(string $modulePath, string $lowerName): array
    {
        $results = [];
        $extensions = ['jpg', 'jpeg', 'png'];

        foreach (['public/images', 'resources/images', 'public/img'] as $rel) {
            $dir = $modulePath.'/'.$rel;
            if (! is_dir($dir)) {
                continue;
            }
            $finder = (new Finder)->files()->in($dir)->name(array_map(fn ($e) => "*.{$e}", $extensions));
            foreach ($finder as $file) {
                $results[] = $this->fileInfo($file->getRealPath());
            }
        }

        $pubDir = public_path("modules/{$lowerName}/images");
        if (is_dir($pubDir)) {
            $finder = (new Finder)->files()->in($pubDir)->name(array_map(fn ($e) => "*.{$e}", $extensions));
            foreach ($finder as $file) {
                $results[] = $this->fileInfo($file->getRealPath());
            }
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    private function fileInfo(string $path): array
    {
        return [
            'path' => $path,
            'relative' => str_replace(base_path().'/', '', $path),
            'name' => basename($path),
            'size' => is_file($path) ? filesize($path) : 0,
            'size_formatted' => $this->formatBytes(is_file($path) ? filesize($path) : 0),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $files
     */
    private function calcTotalSize(array $files): string
    {
        $total = array_sum(array_column($files, 'size'));

        return $this->formatBytes($total);
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

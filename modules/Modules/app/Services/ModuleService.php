<?php

namespace Modules\Modules\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;

class ModuleService
{
    /**
     * Install a module from an uploaded ZIP file.
     *
     * @throws \Exception
     */
    public function install(UploadedFile $file): string
    {
        $tempPath = storage_path('temp/modules');
        $modulesPath = base_path('Modules');

        if (! is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($file->getPathname()) !== true) {
            throw new \Exception('No se pudo extraer el archivo ZIP.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_contains($zip->getNameIndex($i), '..')) {
                $zip->close();
                $this->deleteDirectory($tempPath);
                throw new \Exception('Archivo ZIP inválido: se detectó un intento de path traversal.');
            }
        }

        $zip->extractTo($tempPath);
        $zip->close();

        $extracted = array_diff(scandir($tempPath), ['.', '..']);
        $moduleDir = current($extracted);

        if (! is_dir("$tempPath/$moduleDir")) {
            $subDirs = array_filter(
                scandir("$tempPath/$moduleDir"),
                fn ($item) => is_dir("$tempPath/$moduleDir/$item") && ! str_starts_with($item, '.')
            );

            if (! empty($subDirs)) {
                $moduleDir = "$moduleDir/".current($subDirs);
            }
        }

        if (! file_exists("$tempPath/$moduleDir/module.json")) {
            $this->deleteDirectory($tempPath);
            throw new \Exception('Módulo inválido: no se encontró el archivo module.json.');
        }

        $moduleConfig = json_decode(file_get_contents("$tempPath/$moduleDir/module.json"), true);
        $moduleName = preg_replace('/[^A-Za-z0-9_-]/', '', $moduleConfig['name'] ?? basename($moduleDir));

        // Validate payment-gateway modules before installing
        if (($moduleConfig['type'] ?? null) === 'payment-gateway') {
            $gatewayRegistryClass = 'Modules\\EcommercePayment\\Services\\GatewayRegistry';
            if (class_exists($gatewayRegistryClass)) {
                $errors = $gatewayRegistryClass::validate($moduleConfig);
                if (! empty($errors)) {
                    $this->deleteDirectory($tempPath);
                    throw new \Exception('Gateway inválido: '.implode(' ', $errors));
                }
            }
        }

        if (empty($moduleName)) {
            $this->deleteDirectory($tempPath);
            throw new \Exception('Nombre de módulo inválido en module.json.');
        }

        if (is_dir("$modulesPath/$moduleName")) {
            $this->deleteDirectory($tempPath);
            throw new \Exception("El módulo '$moduleName' ya existe.");
        }

        rename("$tempPath/$moduleDir", "$modulesPath/$moduleName");
        $this->deleteDirectory($tempPath);

        Artisan::call('module:migrate', ['module' => $moduleName, '--force' => true]);

        $this->clearCache();

        return $moduleName;
    }

    /**
     * Update a module's priority and description in its module.json.
     *
     * @throws \Exception
     */
    public function updateConfig(string $modulePath, int $priority, ?string $description): void
    {
        $configPath = $modulePath.DIRECTORY_SEPARATOR.'module.json';

        if (! file_exists($configPath)) {
            throw new \Exception('No se encontró el archivo de configuración del módulo.');
        }

        $config = json_decode(file_get_contents($configPath), true) ?? [];
        $config['priority'] = $priority;
        $config['description'] = $description ?? '';

        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Clear application caches.
     */
    public function clearCache(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }

    /**
     * Recursively delete a directory.
     */
    public function deleteDirectory(string $path): bool
    {
        if (! is_dir($path)) {
            return @unlink($path);
        }

        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (! $this->deleteDirectory($path.DIRECTORY_SEPARATOR.$item)) {
                return false;
            }
        }

        return @rmdir($path);
    }
}

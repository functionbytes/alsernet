<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\Cache;
use Modules\System\Traits\FormatsBytes;

class SystemInfoService
{
    use FormatsBytes;

    /**
     * Get all system information
     */
    public function getAllSystemInfo(): array
    {
        return [
            'environment' => $this->getEnvironmentInfo(),
            'server' => $this->getServerInfo(),
            'php_extensions' => $this->getPHPExtensions(),
            'composer_packages' => $this->getComposerPackages(),
        ];
    }

    /**
     * Get environment information
     */
    public function getEnvironmentInfo(): array
    {
        return [
            'version' => config('app.version', 'Unknown'),
            'framework_version' => app()->version(),
            'timezone' => config('app.timezone'),
            'server_ip' => $this->getServerIP(),
            'debug_mode' => config('app.debug'),
            'storage_writable' => is_writable(storage_path()),
            'cache_writable' => is_writable(storage_path('framework/cache')),
            'app_size' => $this->getDirectorySize(base_path()),
        ];
    }

    /**
     * Get server information
     */
    public function getServerInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'operating_system' => PHP_OS_FAMILY.' '.php_uname('r'),
            'database_driver' => config('database.default'),
            'ssl_installed' => extension_loaded('openssl'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_connection' => config('queue.default'),
            'url_fopen_enabled' => ini_get('allow_url_fopen'),
        ];
    }

    /**
     * Get PHP extensions status
     */
    public function getPHPExtensions(): array
    {
        $extensions = [
            'OpenSSL' => 'openssl',
            'Mbstring' => 'mbstring',
            'PDO' => 'PDO',
            'Curl' => 'curl',
            'Exif' => 'exif',
            'FileInfo' => 'finfo',
            'Tokenizer' => 'tokenizer',
            'GD' => 'gd',
            'Imagick' => 'imagick',
            'Intl' => 'intl',
        ];

        $result = [];
        foreach ($extensions as $name => $extension) {
            $result[$name] = extension_loaded($extension);
        }

        return $result;
    }

    /**
     * Get Composer packages and their versions.
     * Cached for 6 hours — composer.lock rarely changes at runtime.
     */
    public function getComposerPackages(): array
    {
        return Cache::remember('system.composer_packages', now()->addHours(6), function (): array {
            try {
                $composerFile = base_path('composer.lock');

                if (! file_exists($composerFile)) {
                    return [];
                }

                $composer = json_decode(file_get_contents($composerFile), true);

                if (! isset($composer['packages']) && ! isset($composer['packages-dev'])) {
                    return [];
                }

                $packages = array_merge(
                    $composer['packages'] ?? [],
                    $composer['packages-dev'] ?? []
                );

                $result = [];
                foreach ($packages as $package) {
                    $name = $package['name'] ?? 'unknown';
                    $version = $package['version'] ?? 'unknown';

                    // Group packages by vendor
                    [$vendor, $packageName] = explode('/', $name, 2);

                    if (! isset($result[$vendor])) {
                        $result[$vendor] = [];
                    }

                    $result[$vendor][$packageName] = [
                        'full_name' => $name,
                        'version' => $version,
                        'description' => $package['description'] ?? '',
                        'require' => $package['require'] ?? [],
                    ];
                }

                return $result;
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    /**
     * Get the server's public IP address
     */
    private function getServerIP(): string
    {
        if (! empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            return trim($ips[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }

    /**
     * Get directory size in a human-readable format.
     * Limits recursion to 3 levels deep to avoid timeouts on large trees.
     */
    private function getDirectorySize(string $path, int $depth = 0): string
    {
        return $this->formatBytes($this->getDirectorySizeBytes($path, $depth));
    }

    /**
     * Recursively sum file sizes up to a maximum depth.
     */
    private function getDirectorySizeBytes(string $path, int $depth = 0): int
    {
        $files = @scandir($path);

        if ($files === false) {
            return 0;
        }

        $size = 0;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $path.DIRECTORY_SEPARATOR.$file;

            if (@is_dir($filePath)) {
                if ($depth < 3) {
                    $size += $this->getDirectorySizeBytes($filePath, $depth + 1);
                }
            } elseif (file_exists($filePath)) {
                $size += @filesize($filePath);
            }
        }

        return $size;
    }
}

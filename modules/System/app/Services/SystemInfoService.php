<?php

namespace Modules\System\Services;

class SystemInfoService
{
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
     * Get Composer packages and their versions
     */
    public function getComposerPackages(): array
    {
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
    }

    /**
     * Get the server's public IP address
     */
    private function getServerIP(): string
    {
        // Try to get from various sources
        if (! empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle multiple IPs in X-Forwarded-For
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            return trim($ips[0]);
        } elseif (! empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        } else {
            return 'Unknown';
        }
    }

    /**
     * Get directory size in a human-readable format
     */
    private function getDirectorySize(string $path): string
    {
        $size = 0;
        $files = scandir($path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $path.DIRECTORY_SEPARATOR.$file;

            if (is_dir($filePath)) {
                // Limit recursion depth to avoid timeout
                static $depth = 0;
                if ($depth < 3) {
                    $depth++;
                    $size += $this->getDirectorySizeRecursive($filePath);
                    $depth--;
                }
            } else {
                if (file_exists($filePath)) {
                    $size += filesize($filePath);
                }
            }
        }

        return $this->formatBytes($size);
    }

    /**
     * Recursively get directory size
     */
    private function getDirectorySizeRecursive(string $path): int
    {
        $size = 0;
        $files = @scandir($path);

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $path.DIRECTORY_SEPARATOR.$file;

            if (@is_dir($filePath)) {
                $size += $this->getDirectorySizeRecursive($filePath);
            } else {
                $size += @filesize($filePath);
            }
        }

        return $size;
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}

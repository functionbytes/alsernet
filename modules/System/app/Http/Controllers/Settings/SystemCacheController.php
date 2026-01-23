<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Modules\Core\Models\Setting;
use Symfony\Component\Process\Process;

class SystemCacheController extends Controller
{
    /**
     * Find composer executable path - Cross-platform (macOS + Linux)
     */
    private function findComposerPath()
    {
        // 1. Check if custom path is configured in database (Setting)
        $configuredPath = Setting::get('composer_path');
        if (! empty($configuredPath) && file_exists($configuredPath)) {
            return $configuredPath;
        }

        $basePath = base_path();
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $isMac = PHP_OS === 'Darwin';
        $home = getenv('HOME') ?: (getenv('USERPROFILE') ?: '');

        // 2. Try composer.phar in project root
        if (file_exists($basePath.'/composer.phar')) {
            return $basePath.'/composer.phar';
        }

        // 3. macOS specific paths
        if ($isMac) {
            // Laravel Herd (macOS) - prioritize Herd
            $herdPath = $home.'/Library/Application Support/Herd/bin/composer';
            if (! empty($home) && file_exists($herdPath)) {
                return $herdPath;
            }
            // Homebrew M1/M2 Mac
            if (file_exists('/opt/homebrew/bin/composer')) {
                return '/opt/homebrew/bin/composer';
            }
            // Intel Mac Homebrew
            if (file_exists('/usr/local/bin/composer')) {
                return '/usr/local/bin/composer';
            }
        }

        // 4. Linux specific paths
        if (! $isWindows && ! $isMac) {
            // Standard Linux paths
            $linuxPaths = [
                '/usr/local/bin/composer',
                '/usr/bin/composer',
                '/opt/composer/composer',
                $home.'/.composer/vendor/bin/composer',
            ];

            foreach ($linuxPaths as $path) {
                if (! empty($path) && file_exists($path)) {
                    return $path;
                }
            }
        }

        // 5. Universal paths (both systems)
        $universalPaths = [
            '/usr/local/bin/composer',
            '/usr/bin/composer',
        ];

        foreach ($universalPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // 6. Try using 'which' command as fallback
        if (! $isWindows) {
            $output = [];
            $returnCode = 0;
            @exec('which composer 2>/dev/null', $output, $returnCode);

            if ($returnCode === 0 && ! empty($output[0])) {
                $composerPath = trim($output[0]);
                if (file_exists($composerPath)) {
                    return $composerPath;
                }
            }
        }

        // 7. Return null if not found
        return null;
    }

    /**
     * Show the system maintenance page
     */
    public function index()
    {
        // Redirect to unified maintenance page
        return redirect()->route('settings.system.maintenance.index');
    }

    /**
     * Debug: Show detected paths
     */
    public function debug()
    {
        $composerPath = $this->findComposerPath();
        $phpPath = $this->findPhpPath();
        $phpBinary = PHP_BINARY;
        $home = getenv('HOME');

        return response()->json([
            'php_binary' => $phpBinary,
            'home' => $home,
            'composer_path' => $composerPath,
            'php_path' => $phpPath,
            'composer_exists' => $composerPath ? file_exists($composerPath) : false,
            'php_exists' => $phpPath ? file_exists($phpPath) : false,
            'herd_path' => $home ? ($home.'/Library/Application Support/Herd/bin/composer') : 'N/A',
            'herd_exists' => $home && file_exists($home.'/Library/Application Support/Herd/bin/composer'),
        ]);
    }

    /**
     * Clear application cache
     */
    public function clearCache(Request $request)
    {
        try {
            Artisan::call('cache:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache del sistema limpiado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar el cache: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear config cache
     */
    public function clearConfigCache(Request $request)
    {
        try {
            Artisan::call('config:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache de configuración limpiado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar el cache de configuración: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cache configuration
     */
    public function cacheConfig(Request $request)
    {
        try {
            Artisan::call('config:cache');

            return response()->json([
                'success' => true,
                'message' => 'Configuración cacheada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cachear la configuración: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear route cache
     */
    public function clearRouteCache(Request $request)
    {
        try {
            Artisan::call('route:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache de rutas limpiado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar el cache de rutas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear compiled views
     */
    public function clearViewCache(Request $request)
    {
        try {
            Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'Vistas compiladas limpiadas correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar las vistas compiladas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all optimization cache
     */
    public function clearOptimization(Request $request)
    {
        try {
            Artisan::call('optimize:clear');

            return response()->json([
                'success' => true,
                'message' => 'Optimización limpiada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar la optimización: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute composer dump-autoload
     */
    public function composerDumpAutoload(Request $request)
    {
        try {
            $basePath = base_path();
            $composerPath = $this->findComposerPath();

            if (! $composerPath) {
                throw new \Exception('No se pudo encontrar composer en el sistema');
            }

            // Detect if it's a composer.phar file
            $isComposerPhar = strpos($composerPath, 'composer.phar') !== false;

            // Build command with proper array format for Process (safer)
            if ($isComposerPhar) {
                // Use explicit PHP CLI binary path for composer.phar
                $phpPath = $this->findPhpPath();
                $cmd = [$phpPath, $composerPath, 'dump-autoload', '--optimize'];
            } else {
                // Execute composer directly
                $cmd = [$composerPath, 'dump-autoload', '--optimize'];
            }

            // Get the directory of the executable for PATH
            $herdBinPath = dirname($composerPath);

            $process = new Process($cmd, $basePath);
            $process->setTimeout(300); // 5 minutes timeout

            // Set environment variables to include Herd bin path
            $env = $_ENV;
            $env['PATH'] = $herdBinPath.':'.($env['PATH'] ?? '');
            $process->setEnv($env);

            $process->run();

            if (! $process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput() ?: $process->getExitCodeText());
            }

            return response()->json([
                'success' => true,
                'message' => 'Composer dump-autoload ejecutado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar composer dump-autoload: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find PHP executable path
     */
    private function findPhpPath()
    {
        $isMac = PHP_OS === 'Darwin';
        $home = getenv('HOME') ?: (getenv('USERPROFILE') ?: '');

        // Try to find PHP from Herd first on macOS
        if ($isMac && ! empty($home)) {
            $herdPhpPath = $home.'/Library/Application Support/Herd/bin/php';
            if (file_exists($herdPhpPath)) {
                return $herdPhpPath;
            }
        }

        // Try common PHP paths
        $phpPaths = [
            '/opt/homebrew/bin/php',
            '/usr/local/bin/php',
            '/usr/bin/php',
        ];

        foreach ($phpPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Fallback to PHP_BINARY
        return PHP_BINARY ?: 'php';
    }

    /**
     * Execute all maintenance commands at once
     */
    public function executeAll(Request $request)
    {
        $results = [];

        try {
            // 1. Composer dump-autoload
            try {
                $basePath = base_path();
                $composerPath = $this->findComposerPath();

                if (! $composerPath) {
                    $results['composer_dump_autoload'] = [
                        'success' => false,
                        'message' => 'No se pudo encontrar composer en el sistema',
                    ];
                } else {
                    // Detect if it's a composer.phar file
                    $isComposerPhar = strpos($composerPath, 'composer.phar') !== false;

                    // Build command with proper array format for Process (safer)
                    if ($isComposerPhar) {
                        // Use explicit PHP CLI binary path for composer.phar
                        $phpPath = $this->findPhpPath();
                        $cmd = [$phpPath, $composerPath, 'dump-autoload', '--optimize'];
                    } else {
                        // Execute composer directly
                        $cmd = [$composerPath, 'dump-autoload', '--optimize'];
                    }

                    // Get the directory of the executable for PATH
                    $herdBinPath = dirname($composerPath);

                    $process = new Process($cmd, $basePath);
                    $process->setTimeout(300); // 5 minutes timeout

                    // Set environment variables to include Herd bin path
                    $env = $_ENV;
                    $env['PATH'] = $herdBinPath.':'.($env['PATH'] ?? '');
                    $process->setEnv($env);

                    $process->run();
                    $results['composer_dump_autoload'] = [
                        'success' => $process->isSuccessful(),
                        'message' => $process->isSuccessful()
                            ? 'Composer dump-autoload ejecutado'
                            : ($process->getErrorOutput() ?: $process->getExitCodeText()),
                    ];
                }
            } catch (\Exception $e) {
                $results['composer_dump_autoload'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

            // 2. Cache clear
            try {
                Artisan::call('cache:clear');
                $results['cache_clear'] = [
                    'success' => true,
                    'message' => 'Cache limpiado',
                ];
            } catch (\Exception $e) {
                $results['cache_clear'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

            // 3. Config cache
            try {
                Artisan::call('config:cache');
                $results['config_cache'] = [
                    'success' => true,
                    'message' => 'Configuración cacheada',
                ];
            } catch (\Exception $e) {
                $results['config_cache'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

            // 4. Config clear
            try {
                Artisan::call('config:clear');
                $results['config_clear'] = [
                    'success' => true,
                    'message' => 'Cache de configuración limpiado',
                ];
            } catch (\Exception $e) {
                $results['config_clear'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

            // 5. Route clear
            try {
                Artisan::call('route:clear');
                $results['route_clear'] = [
                    'success' => true,
                    'message' => 'Cache de rutas limpiado',
                ];
            } catch (\Exception $e) {
                $results['route_clear'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

            // 6. View clear
            try {
                Artisan::call('view:clear');
                $results['view_clear'] = [
                    'success' => true,
                    'message' => 'Vistas compiladas limpiadas',
                ];
            } catch (\Exception $e) {
                $results['view_clear'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

            // 7. Optimize clear
            try {
                Artisan::call('optimize:clear');
                $results['optimize_clear'] = [
                    'success' => true,
                    'message' => 'Optimización limpiada',
                ];
            } catch (\Exception $e) {
                $results['optimize_clear'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

            $allSuccess = collect($results)->every(fn ($result) => $result['success']);

            return response()->json([
                'success' => $allSuccess,
                'message' => $allSuccess
                    ? 'Todos los comandos ejecutados correctamente'
                    : 'Algunos comandos presentaron errores',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error general: '.$e->getMessage(),
                'results' => $results,
            ], 500);
        }
    }
}

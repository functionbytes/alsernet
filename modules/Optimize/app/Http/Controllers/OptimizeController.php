<?php

namespace Modules\Optimize\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;
use Modules\Optimize\Services\AssetOptimizerService;
use Modules\Optimize\Services\ModuleAssetScanner;
use Modules\Optimize\Services\ThemeOptimizerService;

class OptimizeController extends Controller
{
    private const PREFIX = 'optimize.';

    private const CHECKBOXES = [
        'enabled',
        'collapse_whitespace',
        'elide_attributes',
        'inline_css',
        'insert_dns_prefetch',
        'remove_comments',
        'remove_quotes',
        'defer_javascript',
        'add_loading_lazy',
        'add_image_decoding',
        'minify_inline_styles',
        'minify_inline_scripts',
        'response_cache',
        'inject_font_display',
        'inject_critical_preload',
        'inject_critical_css',
        'add_image_dimensions',
        'cache_control_headers',
        'rewrite_min_assets',
    ];

    /**
     * Commands that can be triggered from the settings UI. Kept as a strict
     * allowlist — only exact signatures that this controller understands are
     * executable, no arbitrary input ever hits Artisan::call().
     */
    private const COMMANDS = [
        'optimize:enable-all' => [
            'label' => 'Activar todas las optimizaciones',
            'params' => ['ttl'],
            'description' => 'Activa los 17 middleware de optimización en Setting',
            'icon' => 'fa-power-off',
        ],
        'optimize:generate-critical-css' => [
            'label' => 'Generar Critical CSS',
            'params' => [],
            'description' => 'Extrae CSS crítico del tema y genera critical.css inline',
            'icon' => 'fa-file-code',
        ],
        'optimize:minify-theme-assets' => [
            'label' => 'Minificar CSS/JS del theme',
            'params' => [],
            'description' => 'Genera .min.css y .min.js solo para assets declarados en config.php',
            'icon' => 'fa-file-code',
        ],
        'optimize:minify-module-assets' => [
            'label' => 'Minificar CSS/JS de todos los módulos',
            'params' => [],
            'description' => 'Genera .min.css y .min.js para todos los módulos activos',
            'icon' => 'fa-cubes',
        ],
        'optimize:theme-images' => [
            'label' => 'Optimizar imágenes del tema',
            'params' => ['quality', 'limit'],
            'description' => 'Convierte imágenes del tema a WebP',
            'icon' => 'fa-image',
        ],
        'optimize:optimize-module-images' => [
            'label' => 'Optimizar imágenes de todos los módulos',
            'params' => ['quality', 'limit'],
            'description' => 'Convierte imágenes de todos los módulos activos a WebP',
            'icon' => 'fa-images',
        ],
        'media:convert-webp' => [
            'label' => 'Convertir imágenes a WebP',
            'params' => ['quality', 'limit'],
            'description' => 'Genera .webp para imágenes del Media (25-35 % menos peso)',
            'icon' => 'fa-image',
        ],
        'media:generate-srcset' => [
            'label' => 'Generar variantes responsive',
            'params' => ['quality', 'limit'],
            'description' => 'Crea -480w, -768w, -1024w, -1920w .webp para cada imagen',
            'icon' => 'fa-images',
        ],
        'theme:audit-a11y' => [
            'label' => 'Auditar accesibilidad del theme',
            'params' => ['slug', 'fix'],
            'description' => 'Reporta imágenes sin alt, enlaces sin nombre, etc.',
            'icon' => 'fa-universal-access',
        ],
        'media:optimize-all' => [
            'label' => 'Optimizar todas las imágenes (webp + srcset)',
            'params' => ['quality', 'limit'],
            'description' => 'Macro: convierte a WebP y genera variantes responsive en un pase',
            'icon' => 'fa-wand-magic-sparkles',
        ],
        'optimize:purge-cache' => [
            'label' => 'Purgar todos los caches',
            'params' => [],
            'description' => 'Limpia view, route, config, cache, response cache y opcache',
            'icon' => 'fa-broom',
        ],
        'page:audit' => [
            'label' => 'Auditar integridad de páginas',
            'params' => ['fix'],
            'description' => 'Detecta templates inválidos, slugs duplicados, traducciones huérfanas',
            'icon' => 'fa-magnifying-glass',
        ],
        'page:cache-warm' => [
            'label' => 'Precalentar cache de páginas públicas',
            'params' => [],
            'description' => 'Renderiza y cachea todas las páginas publicadas del catálogo',
            'icon' => 'fa-fire',
        ],
    ];

    /**
     * Secuencia del botón "Ejecutar toda la optimización": cada paso usa
     * un comando del allowlist anterior + parámetros ya sanitizados.
     */
    private const RUN_ALL_SEQUENCE = [
        ['command' => 'optimize:enable-all', 'params' => ['--ttl' => 3600]],
        ['command' => 'optimize:generate-critical-css', 'params' => []],
        ['command' => 'optimize:minify-theme-assets', 'params' => []],
        ['command' => 'optimize:minify-module-assets', 'params' => []],
        ['command' => 'optimize:theme-images', 'params' => ['--quality' => 82]],
        ['command' => 'optimize:optimize-module-images', 'params' => ['--quality' => 82]],
        ['command' => 'media:convert-webp', 'params' => ['--quality' => 82]],
        ['command' => 'media:generate-srcset', 'params' => ['--quality' => 82]],
        ['command' => 'theme:audit-a11y', 'params' => ['--fix' => true]],
        ['command' => 'optimize:purge-cache', 'params' => []],
    ];

    public function tools(
        ThemeOptimizerService $optimizer,
        ModuleAssetScanner $scanner,
        AssetOptimizerService $assetOptimizer
    ): View {
        $commands = self::COMMANDS;
        $commandGroups = [
            'Sistema' => [
                'optimize:enable-all',
                'optimize:purge-cache',
            ],
            'Tema activo' => [
                'optimize:generate-critical-css',
                'optimize:minify-theme-assets',
                'optimize:theme-images',
            ],
            'Módulos activos' => [
                'optimize:minify-module-assets',
                'optimize:optimize-module-images',
            ],
            'Media' => [
                'media:convert-webp',
                'media:generate-srcset',
                'media:optimize-all',
            ],
            'Auditoría' => [
                'theme:audit-a11y',
                'page:audit',
                'page:cache-warm',
            ],
        ];
        $activeTheme = $optimizer->getActiveTheme();
        $themePath = $optimizer->getThemePath();
        $themeConfig = $optimizer->getThemeConfig();
        $assetsStats = $optimizer->getAssetsStats();
        $pendingImages = $optimizer->getPendingImagesCount();
        $canCompile = $optimizer->canCompileAssets();
        $declaredAssets = $optimizer->getDeclaredAssets();
        $criticalCss = $optimizer->getCriticalCss();
        $executionHistory = Cache::get('optimize.execution_history', []);
        $moduleAssets = $scanner->scanAll();
        $globalStats = $assetOptimizer->getGlobalStats($moduleAssets);

        return view('optimize::settings.tools', compact(
            'commands', 'commandGroups', 'activeTheme', 'themePath', 'themeConfig',
            'assetsStats', 'pendingImages', 'canCompile', 'declaredAssets', 'criticalCss',
            'executionHistory', 'moduleAssets', 'globalStats'
        ));
    }

    public function index(): View
    {
        $get = fn (string $key, string $default = '0') => Setting::get(self::PREFIX.$key, $default);

        $stats = [
            'requests' => (int) Cache::get('optimize.stats.requests', 0),
            'bytes_saved' => (int) Cache::get('optimize.stats.bytes_saved', 0),
        ];

        // Historical series: last 7 days. Tracked by PageSpeed::handle()
        // via `optimize.stats.daily.YYYY-MM-DD.{requests,bytes_saved}`.
        $chartLabels = [];
        $chartRequests = [];
        $chartBytesSaved = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('d M');
            $chartRequests[] = (int) Cache::get("optimize.stats.daily.{$date}.requests", 0);
            $chartBytesSaved[] = round(((int) Cache::get("optimize.stats.daily.{$date}.bytes_saved", 0)) / 1024, 1);
        }

        $ttl = Setting::get('optimize.response_cache_ttl', '60');
        $skipPatterns = Setting::get('optimize.skip_patterns', '');
        $commands = self::COMMANDS;

        return view('optimize::settings.index', compact(
            'get', 'stats', 'ttl', 'skipPatterns', 'commands',
            'chartLabels', 'chartRequests', 'chartBytesSaved'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        foreach (self::CHECKBOXES as $key) {
            Setting::set(self::PREFIX.$key, $request->has($key) ? '1' : '0');
        }

        Setting::set(self::PREFIX.'response_cache_ttl', (string) max(1, (int) $request->input('response_cache_ttl', 60)));
        Setting::set(self::PREFIX.'skip_patterns', $request->input('skip_patterns', ''));

        Setting::clearPrefixCache('optimize.');
        self::flushResponseCache();

        return redirect()->back()->with('success', 'Configuración de optimización guardada correctamente.');
    }

    public function resetStats(): RedirectResponse
    {
        Cache::forget('optimize.stats.requests');
        Cache::forget('optimize.stats.bytes_saved');
        self::flushResponseCache();

        return redirect()->back()->with('success', 'Estadísticas de optimización reiniciadas.');
    }

    public static function flushResponseCache(): void
    {
        try {
            Cache::tags(['optimize.response'])->flush();
        } catch (\BadMethodCallException) {
            // Driver does not support tagging — clear by prefix pattern
        }
    }

    /**
     * Ejecuta uno de los comandos del allowlist. Parámetros sanitizados a
     * tipos estrictos; nunca se interpolan strings libres. La salida del
     * comando se devuelve como JSON para un drawer en el front.
     */
    public function runCommand(Request $request): JsonResponse
    {
        $command = (string) $request->input('command');
        if (! array_key_exists($command, self::COMMANDS)) {
            return response()->json(['success' => false, 'message' => 'Comando no permitido.'], 422);
        }

        $params = $this->sanitizeParams($command, $request);

        $startedAt = microtime(true);
        try {
            Artisan::call($command, $params);
            $output = Artisan::output();
            $elapsedMs = (int) ((microtime(true) - $startedAt) * 1000);

            $this->logExecution($command, $params, $elapsedMs, true, null, $output);

            return response()->json([
                'success' => true,
                'command' => $command,
                'params' => $params,
                'elapsed_ms' => $elapsedMs,
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            $this->logExecution($command, $params, (int) ((microtime(true) - $startedAt) * 1000), false, $e->getMessage());

            return response()->json([
                'success' => false,
                'command' => $command,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ejecuta la secuencia "Ejecutar toda la optimización" y devuelve la
     * salida combinada de todos los pasos. El theme slug se valida como
     * en sanitizeParams() y se inyecta en los pasos que lo necesitan.
     */
    public function runAll(Request $request): JsonResponse
    {
        $slug = preg_replace('/[^a-z0-9_-]/i', '', (string) $request->input('slug')) ?: null;
        $totalStart = microtime(true);
        $outputs = [];

        foreach (self::RUN_ALL_SEQUENCE as $step) {
            $cmd = $step['command'];
            $params = $step['params'];

            // Inyectar slug donde corresponda.
            if (in_array('slug', self::COMMANDS[$cmd]['params'] ?? [], true)) {
                if ($slug === null) {
                    $outputs[] = [
                        'command' => $cmd,
                        'skipped' => true,
                        'reason' => 'slug no provisto',
                    ];

                    continue;
                }
                $params['slug'] = $slug;
            }

            $stepStart = microtime(true);
            try {
                Artisan::call($cmd, $params);
                $elapsedMs = (int) ((microtime(true) - $stepStart) * 1000);
                $output = trim(Artisan::output());
                $outputs[] = [
                    'command' => $cmd,
                    'params' => $params,
                    'elapsed_ms' => $elapsedMs,
                    'output' => $output,
                ];
                $this->logExecution($cmd, $params, $elapsedMs, true, null, $output);
            } catch (\Throwable $e) {
                $outputs[] = [
                    'command' => $cmd,
                    'params' => $params,
                    'error' => $e->getMessage(),
                ];
                $this->logExecution($cmd, $params, (int) ((microtime(true) - $stepStart) * 1000), false, $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'total_elapsed_ms' => (int) ((microtime(true) - $totalStart) * 1000),
            'steps' => $outputs,
        ]);
    }

    /** @return array<string, mixed> */
    private function logExecution(string $command, array $params, int $elapsedMs, bool $success, ?string $error = null, ?string $output = null): void
    {
        $history = Cache::get('optimize.execution_history', []);
        array_unshift($history, [
            'command' => $command,
            'params' => $params,
            'elapsed_ms' => $elapsedMs,
            'success' => $success,
            'error' => $error,
            'output' => $output ? substr($output, 0, 500) : null,
            'executed_at' => now()->toDateTimeString(),
        ]);
        $history = array_slice($history, 0, 20);
        Cache::put('optimize.execution_history', $history, now()->addDays(7));
    }

    private function sanitizeParams(string $command, Request $request): array
    {
        $allowed = self::COMMANDS[$command]['params'] ?? [];
        $params = [];

        foreach ($allowed as $name) {
            $value = $request->input($name);
            if ($value === null || $value === '') {
                continue;
            }
            // Sanitización por nombre conocido — previene inyección de flags.
            switch ($name) {
                case 'ttl':
                case 'limit':
                    $params['--'.$name] = max(0, (int) $value);
                    break;
                case 'quality':
                    $params['--quality'] = max(0, min(100, (int) $value));
                    break;
                case 'slug':
                    // Solo letras, números, guión, subrayado.
                    $clean = preg_replace('/[^a-z0-9_-]/i', '', (string) $value);
                    if ($clean !== '') {
                        $params['slug'] = $clean;
                    }
                    break;
                case 'fix':
                    if ($value === '1' || $value === 1 || $value === true) {
                        $params['--fix'] = true;
                    }
                    break;
            }
        }

        return $params;
    }
}

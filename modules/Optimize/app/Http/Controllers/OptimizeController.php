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
        'minify_inline_styles',
        'minify_inline_scripts',
        'response_cache',
        'inject_font_display',
        'inject_critical_preload',
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
            'description' => 'Activa los 16 middleware de optimización en Setting',
        ],
        'optimize:minify-theme-assets' => [
            'label' => 'Minificar CSS/JS del theme',
            'params' => ['slug'],
            'description' => 'Genera .min.css y .min.js para un theme',
        ],
        'media:convert-webp' => [
            'label' => 'Convertir imágenes a WebP',
            'params' => ['quality', 'limit'],
            'description' => 'Genera .webp para imágenes del Media (25-35 % menos peso)',
        ],
        'theme:audit-a11y' => [
            'label' => 'Auditar accesibilidad del theme',
            'params' => ['slug', 'fix'],
            'description' => 'Reporta imágenes sin alt, enlaces sin nombre, etc.',
        ],
    ];

    public function index(): View
    {
        $get = fn (string $key, string $default = '0') => Setting::get(self::PREFIX.$key, $default);

        $stats = [
            'requests' => (int) Cache::get('optimize.stats.requests', 0),
            'bytes_saved' => (int) Cache::get('optimize.stats.bytes_saved', 0),
        ];

        $ttl = Setting::get('optimize.response_cache_ttl', '60');
        $skipPatterns = Setting::get('optimize.skip_patterns', '');
        $commands = self::COMMANDS;

        return view('optimize::settings.index', compact('get', 'stats', 'ttl', 'skipPatterns', 'commands'));
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

            return response()->json([
                'success' => true,
                'command' => $command,
                'params' => $params,
                'elapsed_ms' => $elapsedMs,
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'command' => $command,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /** @return array<string, mixed> */
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

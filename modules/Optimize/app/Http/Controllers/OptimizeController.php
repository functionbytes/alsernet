<?php

namespace Modules\Optimize\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('optimize::settings.index', compact('get', 'stats', 'ttl', 'skipPatterns'));
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
}

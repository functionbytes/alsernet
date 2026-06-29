<?php

namespace Modules\Cache\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Cache\Http\Requests\Settings\CacheSettingsRequest;
use Modules\Core\Models\Setting;
use Modules\Page\Services\PageCacheService;
use Modules\System\Helpers\ModuleStatusHelper;

class CacheSettingsController extends Controller
{
    private const PREFIX = 'cache.';

    public function __construct()
    {
        $this->middleware('can:Cache.settings.index')->only('index', 'monitor', 'stats', 'redisStats');
        $this->middleware('can:Cache.settings.update')->only('update', 'flush');
    }

    public function index(): View
    {
        $get = fn (string $key, mixed $default = '') => Setting::get(self::PREFIX.$key, $default);

        return view('Cache::settings.index', [
            'get' => $get,
        ]);
    }

    public function monitor(): View
    {
        return view('Cache::settings.monitor');
    }

    public function stats(): JsonResponse
    {
        if (! ModuleStatusHelper::isModuleEnabled('Page')) {
            return response()->json(['error' => 'Page module not enabled'], 404);
        }

        return response()->json(PageCacheService::getStats());
    }

    public function redisStats(): JsonResponse
    {
        try {
            $info = Cache::remember('redis.stats', 5, fn () => Redis::connection()->info());

            $hits = (int) ($info['keyspace_hits'] ?? 0);
            $misses = (int) ($info['keyspace_misses'] ?? 0);
            $total = $hits + $misses;

            return response()->json([
                'connected' => true,
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => (int) ($info['connected_clients'] ?? 0),
                'keyspace_hits' => $hits,
                'keyspace_misses' => $misses,
                'hit_rate' => $total > 0 ? round($hits / $total * 100, 1) : 0,
                'uptime_days' => round(((int) ($info['uptime_in_seconds'] ?? 0)) / 86400, 1),
            ]);
        } catch (\Throwable $e) {
            Log::error('Redis stats unavailable', ['error' => $e->getMessage()]);

            return response()->json(['connected' => false, 'error' => 'Error al gestionar la caché.']);
        }
    }

    public function flush(Request $request): JsonResponse
    {
        $type = $request->input('type', 'all');

        try {
            match ($type) {
                'menu' => Cache::forget('menu'),
                'settings' => Cache::forget('settings'),
                'pages' => Cache::tags(['pages'])->flush(),
                default => Cache::flush(),
            };

            return response()->json(['success' => true, 'type' => $type]);
        } catch (\Throwable $e) {
            Log::error('Cache flush failed', ['error' => $e->getMessage(), 'type' => $type]);

            return response()->json(['success' => false, 'message' => 'Error al gestionar la caché.'], 500);
        }
    }

    public function update(CacheSettingsRequest $request): RedirectResponse
    {
        $checkboxes = [
            'admin_menu_enabled',
            'frontend_menu_enabled',
            'user_avatars_enabled',
            'sitemap_enabled',
        ];

        // Only include pages_enabled if the Page module is active
        if (ModuleStatusHelper::isModuleEnabled('Page')) {
            $checkboxes[] = 'pages_enabled';
        }

        DB::transaction(function () use ($request, $checkboxes): void {
            foreach ($checkboxes as $field) {
                Setting::set(self::PREFIX.$field, $request->has($field) ? '1' : '0');
            }

            Setting::set(self::PREFIX.'sitemap_ttl', $request->validated()['sitemap_ttl']);

            // Only save pages_ttl if the Page module is active
            if (ModuleStatusHelper::isModuleEnabled('Page') && $request->has('pages_ttl')) {
                Setting::set(self::PREFIX.'pages_ttl', $request->validated()['pages_ttl']);
            }
        });

        Setting::clearPrefixCache(self::PREFIX);

        return redirect()->back()->with('success', 'Configuracion de cache actualizada correctamente.');
    }
}

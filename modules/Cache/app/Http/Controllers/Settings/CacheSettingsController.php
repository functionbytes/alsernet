<?php

namespace Modules\Cache\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Cache\Http\Requests\Settings\CacheRequest;
use Modules\Core\Models\Setting;

class CacheController extends Controller
{
    private const PREFIX = 'cache.';

    public function index(): View
    {
        $get = fn (string $key, mixed $default = '') => Setting::get(self::PREFIX.$key, $default);

        return view('Cache::settings.index', [
            'get' => $get,
        ]);
    }

    public function update(CacheRequest $request): RedirectResponse
    {
        $checkboxes = [
            'admin_menu_enabled',
            'frontend_menu_enabled',
            'user_avatars_enabled',
            'sitemap_enabled',
        ];

        // Only include pages_enabled if the Page module is active
        if (module_exists('Page')) {
            $checkboxes[] = 'pages_enabled';
        }

        foreach ($checkboxes as $field) {
            Setting::set(self::PREFIX.$field, $request->has($field) ? '1' : '0');
        }

        Setting::set(self::PREFIX.'sitemap_ttl', $request->validated()['sitemap_ttl']);

        // Only save pages_ttl if the Page module is active
        if (module_exists('Page') && $request->has('pages_ttl')) {
            Setting::set(self::PREFIX.'pages_ttl', $request->validated()['pages_ttl']);
        }

        Setting::clearPrefixCache(self::PREFIX);

        return redirect()->back()->with('success', 'Configuracion de cache actualizada correctamente.');
    }
}

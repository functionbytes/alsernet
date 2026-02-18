<?php

namespace Modules\CacheSettings\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\CacheSettings\Http\Requests\Settings\CacheSettingsRequest;
use Modules\Core\Models\Setting;

class CacheSettingsController extends Controller
{
    private const PREFIX = 'cache.';

    public function index(): View
    {
        $get = fn (string $key, mixed $default = '') => Setting::get(self::PREFIX.$key, $default);

        return view('cachesettings::settings.index', [
            'get' => $get,
        ]);
    }

    public function update(CacheSettingsRequest $request): RedirectResponse
    {
        $checkboxes = [
            'admin_menu_enabled',
            'frontend_menu_enabled',
            'user_avatars_enabled',
            'sitemap_enabled',
        ];

        foreach ($checkboxes as $field) {
            Setting::set(self::PREFIX.$field, $request->has($field) ? '1' : '0');
        }

        Setting::set(self::PREFIX.'sitemap_ttl', $request->validated()['sitemap_ttl']);

        Setting::clearPrefixCache(self::PREFIX);

        return redirect()->back()->with('success', 'Configuracion de cache actualizada correctamente.');
    }
}
